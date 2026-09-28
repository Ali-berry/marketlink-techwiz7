<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

// author ko notification mein reason dikhta hai, khali nahi ho sakta
class RejectCommunityPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
