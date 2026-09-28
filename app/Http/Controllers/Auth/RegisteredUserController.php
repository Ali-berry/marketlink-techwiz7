<?php

namespace App\Http\Controllers\Auth;

use App\Enums\FarmerApprovalStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\User;
use App\Notifications\FarmerApprovalStatusChanged;
use App\Services\FarmerApprovalService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(private readonly FarmerApprovalService $farmerApprovalService)
    {
    }

    public function create(): View
    {
        return view('auth.register', [
            'markets' => Market::active()->orderBy('name')->get(),
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $isFarmerAccount = $request->isFarmerAccount();

        // farmer address nahi likhta, market chunta hai - account aur stall shuru mein usi market pe,
        // exact jagah baad mein stall profile se
        $selectedMarket = $isFarmerAccount ? Market::findOrFail($validated['market_id']) : null;

        $user = User::create([
            // farmer ka account asli insaan ke naam pe, stall ke naam pe nahi
            'name' => $isFarmerAccount ? $validated['contact_person'] : $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $isFarmerAccount ? $selectedMarket->address : ($validated['address'] ?? null),
            'latitude' => $isFarmerAccount ? $selectedMarket->latitude : ($validated['latitude'] ?? null),
            'longitude' => $isFarmerAccount ? $selectedMarket->longitude : ($validated['longitude'] ?? null),
            'password' => Hash::make($validated['password']),
            'role' => $isFarmerAccount ? UserRole::Farmer->value : UserRole::Customer->value,
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

            // abhi sirf chuni hui market, baqi farmer.stall.edit se add ho sakti hain
            $farmerProfile->markets()->attach($selectedMarket->id);

            // admin approve/suspend wala notification hi - us ka "pending" hissa isi ke liye bana hai
            $user->notify(new FarmerApprovalStatusChanged($farmerProfile));
            $this->farmerApprovalService->notifyAdminsOfNewSignup($farmerProfile);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
