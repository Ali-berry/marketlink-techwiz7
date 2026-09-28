<?php

namespace App\Http\Requests\Customer;

use App\Models\FarmerProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
{
    // sirf un farmers ko message jo customer ko dikh sakte hain
    public function authorize(): bool
    {
        $farmer = $this->route('farmer');

        return $farmer && FarmerProfile::visibleToCustomers()->whereKey($farmer->id)->exists();
    }

    public function rules(): array
    {
        return [
            // text, photo ya voice note - kam az kam ek chahiye
            'body' => ['nullable', 'string', 'max:2000', 'required_without_all:image,voice_note'],
            'image' => ['nullable', 'image', 'max:5120'],
            'voice_note' => ['nullable', 'file', 'mimes:webm,ogg,mp3,wav,m4a,mp4', 'max:10240'],
            // recording kitne second chali, browser mein time kiya - file pe bharosa nahi, Message::voiceNoteDurationText() dekho
            'voice_note_duration_seconds' => ['nullable', 'integer', 'min:0', 'max:600'],
            'related_order_id' => [
                'nullable',
                'integer',
                Rule::exists('orders', 'id')
                    ->where('customer_id', $this->user()->id)
                    ->where('farmer_profile_id', $this->route('farmer')->id),
            ],
        ];
    }
}
