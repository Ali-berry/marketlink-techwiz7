<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $conversations = $request->user()->conversationsAsCustomer()
            ->with(['farmer', 'latestMessage'])
            ->withCount(['messages as unread_messages_count' => fn ($query) => $query
                ->where('sender_id', '!=', $request->user()->id)
                ->whereNull('read_at')])
            ->orderByDesc('updated_at')
            ->get();

        return view('customer.messages.index', compact('conversations'));
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $this->abortUnlessOwnedByCustomer($request, $conversation);

        $conversation->load(['farmer', 'messages.sender', 'messages.relatedOrder']);
        $conversation->markReadFor($request->user());

        return view('customer.messages.show', compact('conversation'));
    }

    private function abortUnlessOwnedByCustomer(Request $request, Conversation $conversation): void
    {
        abort_unless($conversation->customer_id === $request->user()->id, 403);
    }
}
