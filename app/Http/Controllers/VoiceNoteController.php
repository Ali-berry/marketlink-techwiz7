<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

// PHP ka built-in server .webm ko "video/webm" bhejta hai aur Chrome ka <audio> usay play nahi karta.
// isi file ko sahi audio header ke saath yahan se serve karte hain
class VoiceNoteController extends Controller
{
    public function show(string $filename): StreamedResponse
    {
        $path = 'chat-voice-notes/'.$filename;

        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, $filename, [
            'Content-Type' => 'audio/webm',
        ]);
    }
}
