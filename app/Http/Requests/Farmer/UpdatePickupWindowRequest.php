<?php

namespace App\Http\Requests\Farmer;

// create wale rules aur overlap check, bas edit hone wali window ko chhod ke
class UpdatePickupWindowRequest extends StorePickupWindowRequest
{
    public function authorize(): bool
    {
        $pickupWindow = $this->route('pickupWindow');

        return $pickupWindow && $this->user()->farmerProfile?->id === $pickupWindow->farmer_profile_id;
    }
}
