<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStallProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->farmerProfile !== null;
    }

    public function rules(): array
    {
        return [
            'stall_name' => ['required', 'string', 'max:100'],
            'contact_person' => ['required', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'farming_experience' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'order_cutoff_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            // urgent hours market ke timezone mein parhe jate hain, is liye kam az kam ek market chahiye
            'markets' => ['required_if_accepted:accepts_urgent_orders', 'nullable', 'array'],
            'markets.*' => ['integer', 'exists:markets,id'],
            'stall_numbers' => ['nullable', 'array'],
            'stall_numbers.*' => ['nullable', 'string', 'max:20'],
            'accepts_urgent_orders' => ['nullable', 'boolean'],
            'ai_auto_confirms_urgent' => ['nullable', 'boolean'],
            'ai_marks_urgent_ready' => ['nullable', 'boolean'],
            // upar wala switch off ho to input disabled hai aur request mein nahi aata
            'urgent_prep_minutes' => ['required_if_accepted:ai_marks_urgent_ready', 'nullable', 'integer', 'min:0', 'max:60'],
            'urgent_pickup_starts_at' => ['required_if_accepted:accepts_urgent_orders', 'nullable', 'date_format:H:i'],
            'urgent_pickup_ends_at' => ['required_if_accepted:accepts_urgent_orders', 'nullable', 'date_format:H:i', 'different:urgent_pickup_starts_at'],
            'max_urgent_orders_per_hour' => ['required', 'integer', 'min:1', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'markets.required_if_accepted' => 'Pick at least one market before turning on urgent orders.',
            'urgent_pickup_starts_at.required_if_accepted' => 'Set the time you start taking urgent pickups.',
            'urgent_pickup_ends_at.required_if_accepted' => 'Set the time you stop taking urgent pickups.',
            'urgent_prep_minutes.required_if_accepted' => 'Set how many minutes you need to get an urgent order ready.',
        ];
    }
}
