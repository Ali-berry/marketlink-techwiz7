<?php

namespace App\Http\Controllers\Auth;

use App\Enums\FarmerApprovalStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\User;
use App\Notifications\FarmerApprovalStatusChanged;
use App\Services\FarmerApprovalService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function __construct(private readonly FarmerApprovalService $farmerApprovalService)
    {
    }

    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $exception) {
            Log::warning('Google sign-in failed', ['error' => $exception->getMessage()]);

            return redirect()->route('login')->with('status', "Couldn't sign in with Google - please try again.");
        }

        $existingUser = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($existingUser) {
            // normal tareeqe se bana account pehli baar Google se aaya - link kar do taake agli baar google_id se match ho
            if (! $existingUser->google_id) {
                $existingUser->update(['google_id' => $googleUser->getId()]);
            }

            Auth::login($existingUser);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        // bilkul naya banda - Google sirf naam aur email deta hai, customer hai ya farmer ye poochna parega
        session(['google_signup' => [
            'google_id' => $googleUser->getId(),
            'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'New user',
            'email' => $googleUser->getEmail(),
        ]]);

        return redirect()->route('auth.google.complete');
    }

    public function showComplete(): View|RedirectResponse
    {
        if (! session()->has('google_signup')) {
            return redirect()->route('register');
        }

        return view('auth.google-complete', [
            'googleSignup' => session('google_signup'),
            'markets' => Market::active()->orderBy('name')->get(),
        ]);
    }

    public function storeComplete(Request $request): RedirectResponse
    {
        $googleSignup = session('google_signup');

        if (! $googleSignup) {
            return redirect()->route('register');
        }

        $isFarmerAccount = $request->input('account_type') === 'farmer';

        $validated = $request->validate([
            'account_type' => ['required', Rule::in(['customer', 'farmer'])],
            'phone' => ['required', 'string', 'max:30'],
            'stall_name' => [Rule::requiredIf($isFarmerAccount), 'nullable', 'string', 'max:100'],
            'contact_person' => [Rule::requiredIf($isFarmerAccount), 'nullable', 'string', 'max:100'],
            'market_id' => [Rule::requiredIf($isFarmerAccount), 'nullable', 'integer', 'exists:markets,id'],
            // sirf customer - farmer ka address us ki market se aata hai
            'address' => [Rule::requiredIf(! $isFarmerAccount), 'nullable', 'string', 'max:500'],
            'area' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $selectedMarket = $isFarmerAccount ? Market::findOrFail($validated['market_id']) : null;

        $user = User::create([
            'name' => $isFarmerAccount ? $validated['contact_person'] : $googleSignup['name'],
            'email' => $googleSignup['email'],
            'google_id' => $googleSignup['google_id'],
            'phone' => $validated['phone'],
            'address' => $isFarmerAccount ? $selectedMarket->address : ($validated['address'] ?? null),
            'latitude' => $isFarmerAccount ? $selectedMarket->latitude : ($validated['latitude'] ?? null),
            'longitude' => $isFarmerAccount ? $selectedMarket->longitude : ($validated['longitude'] ?? null),
            // Google se password nahi aata - random hash, jab tak user khud set na kare sirf Google se login
            'password' => Hash::make(Str::random(40)),
            'role' => $isFarmerAccount ? UserRole::Farmer->value : UserRole::Customer->value,
            'email_verified_at' => now(),
        ]);

        if ($isFarmerAccount) {
            $farmerProfile = $user->farmerProfile()->create([
                'stall_name' => $validated['stall_name'],
                'slug' => FarmerProfile::uniqueSlugFor($validated['stall_name']),
                'contact_person' => $validated['contact_person'],
                'address' => $selectedMarket->address,
                'latitude' => $selectedMarket->latitude,
                'longitude' => $selectedMarket->longitude,
                'order_cutoff_hours' => config('marketlink.default_order_cutoff_hours'),
                'approval_status' => FarmerApprovalStatus::Pending->value,
            ]);

            $farmerProfile->markets()->attach($selectedMarket->id);

            $user->notify(new FarmerApprovalStatusChanged($farmerProfile));
            $this->farmerApprovalService->notifyAdminsOfNewSignup($farmerProfile);
        }

        event(new Registered($user));

        session()->forget('google_signup');

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
