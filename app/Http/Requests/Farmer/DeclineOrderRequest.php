<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class DeclineOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order && $this->user()->farmerProfile?->id === $order->farmer_profile_id;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
