<?php

namespace App\Http\Requests\Community;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
