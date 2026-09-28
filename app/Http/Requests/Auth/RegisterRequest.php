<?php

namespace App\Http\Requests\Auth;

use App\Rules\ValidDeliverableEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function isFarmerAccount(): bool
    {
        return $this->input('account_type') === 'farmer';
    }

    public function rules(): array
    {
        $sharedRules = [
            'account_type' => ['required', Rule::in(['customer', 'farmer'])],
            // bail aur Abstract sab se aakhir mein, taake credit sirf sahi aur naye email pe lage
            'email' => ['bail', 'required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email', new ValidDeliverableEmail],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];

        // farmer address nahi likhta, market chunta hai - RegisteredUserController usi market se
        // address / lat / long bhar deta hai
        if ($this->isFarmerAccount()) {
            return [
                ...$sharedRules,
                'stall_name' => ['required', 'string', 'max:100'],
                'contact_person' => ['required', 'string', 'max:100'],
                'market_id' => ['required', 'integer', 'exists:markets,id'],
            ];
        }

        return [
            ...$sharedRules,
            'name' => ['required', 'string', 'max:255'],
            // SRS har customer se address maangta hai. Area/Location alag hai, wo nearest-market sorting ke liye
            'address' => ['required', 'string', 'max:500'],
            'area' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
