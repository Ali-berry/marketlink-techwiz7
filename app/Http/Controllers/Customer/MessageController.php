<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\FarmerProfile;
use Illuminate\Http\RedirectResponse;

class MessageController extends Controller
{
    // naya conversation sirf yahan banta hai - farmer ka aisa route nahi, wo sirf reply kar sakta hai
    public function store(StoreMessageRequest $request, FarmerProfile $farmer): RedirectResponse
    {
        $customer = $request->user();
        $validated = $request->validated();

        // har customer-farmer pair ka ek thread, pehle se ho to wahi
        $conversation = Conversation::firstOrCreate([
            'customer_id' => $customer->id,
            'farmer_profile_id' => $farmer->id,
        ]);

        $conversation->messages()->create([
            'sender_id' => $customer->id,
            'body' => $validated['body'] ?? null,
            'image_path' => $request->hasFile('image') ? $request->file('image')->store('chat-images', 'public') : null,
            'voice_note_path' => $request->hasFile('voice_note') ? $request->file('voice_note')->store('chat-voice-notes', 'public') : null,
            'voice_note_duration_seconds' => $validated['voice_note_duration_seconds'] ?? null,
            'related_order_id' => $validated['related_order_id'] ?? null,
        ]);

        return redirect()->route('customer.messages.show', $conversation);
    }
}
