<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class AddToBasketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:'.max($product->stock_quantity, 1)],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.max' => 'There are only :max left in stock.',
        ];
    }
}
