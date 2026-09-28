<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ToggleFavouriteProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wants_restock_alert' => ['sometimes', 'boolean'],
        ];
    }
}
