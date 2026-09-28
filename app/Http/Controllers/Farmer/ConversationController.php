<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\ReplyToMessageRequest;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// jaan boojh ke "naya conversation" method nahi - farmer sirf customer ke shuru kiye thread mein reply karta hai
class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $farmer = $request->user()->farmerProfile;

        abort_if(! $farmer, 403, 'Your farmer profile is missing. Please contact the admin.');

        $conversations = $farmer->conversations()
            ->with(['customer', 'latestMessage'])
            ->withCount(['messages as unread_messages_count' => fn ($query) => $query
                ->where('sender_id', '!=', $request->user()->id)
                ->whereNull('read_at')])
            ->orderByDesc('updated_at')
            ->get();

        return view('farmer.messages.index', compact('conversations'));
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $this->abortUnlessOwnedByFarmer($request, $conversation);

        $conversation->load(['customer', 'messages.sender', 'messages.relatedOrder']);
        $conversation->markReadFor($request->user());

        return view('farmer.messages.show', compact('conversation'));
    }

    public function reply(ReplyToMessageRequest $request, Conversation $conversation): RedirectResponse
    {
        $validated = $request->validated();

        $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $validated['body'] ?? null,
            'image_path' => $request->hasFile('image') ? $request->file('image')->store('chat-images', 'public') : null,
            'voice_note_path' => $request->hasFile('voice_note') ? $request->file('voice_note')->store('chat-voice-notes', 'public') : null,
            'voice_note_duration_seconds' => $validated['voice_note_duration_seconds'] ?? null,
        ]);

        return redirect()->route('farmer.messages.show', $conversation);
    }

    private function abortUnlessOwnedByFarmer(Request $request, Conversation $conversation): void
    {
        abort_unless($conversation->farmer_profile_id === $request->user()->farmerProfile?->id, 403);
    }
}
