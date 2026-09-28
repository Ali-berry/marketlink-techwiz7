<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

// login page pe judges ke liye "Enter as customer/farmer/admin" buttons.
// config('marketlink.show_demo_logins') yahan bhi check hota hai taake URL guess karke na khule
class DemoLoginController extends Controller
{
    private const DEMO_ACCOUNT_EMAILS = [
        'customer' => 'sara@marketlink.test',
        'farmer' => 'greenvalley@marketlink.test',
        'admin' => 'admin@marketlink.test',
    ];

    public function __invoke(string $role): RedirectResponse
    {
        abort_unless(config('marketlink.show_demo_logins'), 404);
        abort_unless(array_key_exists($role, self::DEMO_ACCOUNT_EMAILS), 404);

        $demoUser = User::where('email', self::DEMO_ACCOUNT_EMAILS[$role])->firstOrFail();

        Auth::login($demoUser);

        return redirect(route('dashboard', absolute: false));
    }
}
