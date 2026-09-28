<?php

namespace App\Http\Requests\Farmer;

use App\Enums\ProductAvailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// "sold out / not this week / available" wale chhote buttons
class UpdateProductAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product && $this->user()->farmerProfile?->id === $product->farmer_profile_id;
    }

    public function rules(): array
    {
        return [
            'availability' => ['required', Rule::enum(ProductAvailability::class)],
        ];
    }
}
