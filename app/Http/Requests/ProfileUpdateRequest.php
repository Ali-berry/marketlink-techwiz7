<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\ValidDeliverableEmail;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $emailRules = [
            'bail',
            'required',
            'string',
            'lowercase',
            'email',
            'max:255',
            Rule::unique(User::class)->ignore($this->user()->id),
        ];

        // same email ke saath save karne pe Abstract ka credit na lage
        $emailIsChanging = $this->input('email') !== $this->user()->email;

        if ($emailIsChanging) {
            $emailRules[] = new ValidDeliverableEmail;
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
        ];
    }
}
