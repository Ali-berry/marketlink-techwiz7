<?php

namespace App\Http\Requests\Farmer;

use App\Models\Market;
use App\Models\PickupWindow;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePickupWindowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->farmerProfile !== null;
    }

    public function rules(): array
    {
        $farmerMarketIds = $this->user()->farmerProfile->markets()->pluck('markets.id');

        return [
            'market_id' => ['required', Rule::in($farmerMarketIds)],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'max_orders' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $market = Market::find($this->input('market_id'));

            if (! $market) {
                return;
            }

            $this->validateDayIsOpen($validator, $market);
            $this->validateInsideOpeningHours($validator, $market);
            $this->validateNoOverlap($validator, $market);
        });
    }

    private function validateDayIsOpen(Validator $validator, Market $market): void
    {
        $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $chosenDayName = $dayNames[(int) $this->input('day_of_week')];

        if (! in_array($chosenDayName, $market->operating_days, true)) {
            $validator->errors()->add('day_of_week', $market->name.' is not open on '.ucfirst($chosenDayName).'.');
        }
    }

    private function validateInsideOpeningHours(Validator $validator, Market $market): void
    {
        if ($this->input('starts_at') < $market->opens_at) {
            $validator->errors()->add('starts_at', $market->name.' only opens from '.$market->timingText().'.');
        }

        if ($this->input('ends_at') > $market->closes_at) {
            $validator->errors()->add('ends_at', $market->name.' closes at '.Carbon::parse($market->closes_at)->format('g:i A').'.');
        }
    }

    // same market aur din pe overlap = ek doosre ke khatam hone se pehle shuru ho jaye
    private function validateNoOverlap(Validator $validator, Market $market): void
    {
        $overlappingSlotExists = PickupWindow::query()
            ->where('farmer_profile_id', $this->user()->farmerProfile->id)
            ->where('market_id', $market->id)
            ->where('day_of_week', $this->input('day_of_week'))
            ->when($this->route('pickupWindow'), fn ($query, $pickupWindow) => $query->whereKeyNot($pickupWindow->id))
            ->where('starts_at', '<', $this->input('ends_at'))
            ->where('ends_at', '>', $this->input('starts_at'))
            ->exists();

        if ($overlappingSlotExists) {
            $validator->errors()->add('starts_at', 'This overlaps with another pickup slot you already have for this market and day.');
        }
    }
}
