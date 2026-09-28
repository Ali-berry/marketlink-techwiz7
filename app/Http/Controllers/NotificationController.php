<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // bell dropdown ka "mark all as read", har role ke liye
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
