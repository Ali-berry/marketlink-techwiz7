<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class ReplyToMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation && $this->user()->farmerProfile?->id === $conversation->farmer_profile_id;
    }

    public function rules(): array
    {
        return [
            // customer side wala "kam az kam ek" rule
            'body' => ['nullable', 'string', 'max:2000', 'required_without_all:image,voice_note'],
            'image' => ['nullable', 'image', 'max:5120'],
            'voice_note' => ['nullable', 'file', 'mimes:webm,ogg,mp3,wav,m4a,mp4', 'max:10240'],
            'voice_note_duration_seconds' => ['nullable', 'integer', 'min:0', 'max:600'],
        ];
    }
}
