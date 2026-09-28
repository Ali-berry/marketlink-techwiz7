<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        // customer signup ko account type, phone aur address chahiye, aur password app ka rule pass kare (8+, mixed case, number, symbol)
        $response = $this->post('/register', [
            'account_type' => 'customer',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '(214) 555-0199',
            'address' => '100 Main St, Dallas, TX 75201',
            'password' => 'Test@1234',
            'password_confirmation' => 'Test@1234',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
