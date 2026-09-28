<?php

namespace App\Http\Requests\Admin;

use App\Models\Market;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMarketRequest extends FormRequest
{
    private const DAY_NAMES = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:60'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'operating_days' => ['required', 'array', 'min:1'],
            'operating_days.*' => [Rule::in(self::DAY_NAMES)],
            'opens_at' => ['required', 'date_format:H:i'],
            'closes_at' => ['required', 'date_format:H:i', 'after:opens_at'],
            'timezone' => ['required', Rule::in(array_keys(Market::TIMEZONES))],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
