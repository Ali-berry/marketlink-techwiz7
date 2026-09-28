<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'icon' => ['required', 'string', 'max:60'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
