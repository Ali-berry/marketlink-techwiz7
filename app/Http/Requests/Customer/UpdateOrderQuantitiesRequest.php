<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateOrderQuantitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order && $order->customer_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'quantities' => ['required', 'array', 'min:1'],
            'quantities.*' => ['required', 'integer', 'min:1', 'max:9999'],
        ];
    }

    // "quantities" ki har key isi order ki item id honi chahiye
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $order = $this->route('order');
            $ownItemIds = $order->items()->pluck('id')->all();

            foreach (array_keys($this->input('quantities', [])) as $itemId) {
                if (! in_array((int) $itemId, $ownItemIds, true)) {
                    $validator->errors()->add('quantities', 'That item does not belong to this order.');

                    return;
                }
            }
        });
    }
}
