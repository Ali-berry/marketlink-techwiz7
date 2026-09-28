<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class ReplyToReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review && $this->user()->farmerProfile?->id === $review->farmer_profile_id;
    }

    public function rules(): array
    {
        return [
            'farmer_reply' => ['required', 'string', 'max:1000'],
        ];
    }
}
