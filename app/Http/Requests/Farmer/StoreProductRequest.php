<?php

namespace App\Http\Requests\Farmer;

use App\Enums\ProductAvailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->farmerProfile !== null;
    }

    public function rules(): array
    {
        return [
            'product_category_id' => ['required', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', Rule::in(config('marketlink.product_units'))],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'weekly_default_quantity' => ['required', 'integer', 'min:0'],
            'availability' => ['required', Rule::enum(ProductAvailability::class)],
            'image' => ['nullable', 'image', 'max:4096'],
        ];
    }
}
