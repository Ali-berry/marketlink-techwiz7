<?php

namespace App\Http\Requests\Customer;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    // sirf apne completed orders, aur sirf wo products jo us order mein the
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $order = Order::where('customer_id', $this->user()->id)->completed()->find($this->input('order_id'));

            if (! $order) {
                $validator->errors()->add('order_id', 'You can only review your own completed orders.');

                return;
            }

            $productId = $this->input('product_id');

            if ($productId && ! $order->items()->where('product_id', $productId)->exists()) {
                $validator->errors()->add('product_id', 'That product was not part of this order.');
            }
        });
    }
}
