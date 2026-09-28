<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

// moderation mein products aur reviews dono ka "hide" button
class HideContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
