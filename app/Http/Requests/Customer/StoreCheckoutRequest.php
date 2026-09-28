<?php

namespace App\Http\Requests\Customer;

use App\Models\Market;
use App\Models\Order;
use App\Models\PickupWindow;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'market_id' => ['required', 'integer', 'exists:markets,id'],
            // har "slot + date" ka ek radio, value "windowId_YYYY-MM-DD"
            'pickup_choice' => ['required', 'regex:/^\d+_\d{4}-\d{2}-\d{2}$/'],
            'customer_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function pickupWindowId(): int
    {
        return (int) explode('_', $this->input('pickup_choice'))[0];
    }

    public function pickupDate(): Carbon
    {
        return Carbon::parse(explode('_', $this->input('pickup_choice'))[1]);
    }

    // tooti hui form pe yahan achha error mil jaye. Asal race-safe check PlaceOrderFromBasket mein hai
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $farmer = $this->route('farmer');
            $market = Market::find($this->input('market_id'));

            if (! $market || ! $farmer->markets->contains('id', $market->id)) {
                $validator->errors()->add('market_id', 'Choose a market this farmer actually sells at.');

                return;
            }

            if (! $market->is_active) {
                $validator->errors()->add('market_id', $market->name.' is closed on MarketLink right now. Please choose another market.');

                return;
            }

            $pickupWindow = PickupWindow::find($this->pickupWindowId());

            if (! $pickupWindow || $pickupWindow->farmer_profile_id !== $farmer->id || $pickupWindow->market_id !== $market->id) {
                $validator->errors()->add('pickup_choice', 'Choose a valid pickup slot for this market.');

                return;
            }

            $pickupDate = $this->pickupDate();
            $isBeforeMarketToday = $pickupDate->toDateString() < $market->localNow()->toDateString();

            if ($pickupDate->dayOfWeek !== $pickupWindow->day_of_week || $isBeforeMarketToday) {
                $validator->errors()->add('pickup_choice', "That date doesn't match the pickup slot's day.");

                return;
            }

            if (! Order::isWithinCutoff($market, $pickupDate, $pickupWindow->starts_at, $farmer->order_cutoff_hours)) {
                $validator->errors()->add('pickup_choice', 'That pickup time is too close now - please choose a later date.');
            }
        });
    }
}
