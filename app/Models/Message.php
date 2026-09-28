<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Message extends Model
{
    // rows edit nahi hoti, sirf insert aur baad mein read mark
    const UPDATED_AT = null;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'image_path',
        'voice_note_path',
        'voice_note_duration_seconds',
        'related_order_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    // conversation ka updated_at badhne se inbox bina extra query ke newest first sort hota hai
    protected $touches = ['conversation'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function relatedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'related_order_id');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    // apne route se serve hota hai (symlink se nahi) taake audio/webm Content-Type sahi jaye - VoiceNoteController dekho
    public function voiceNoteUrl(): ?string
    {
        return $this->voice_note_path ? route('voice-notes.show', basename($this->voice_note_path)) : null;
    }

    // "0:07" - recording ke waqt save kiya hua column. Chrome ki webm file mein duration nahi hoti,
    // <audio> akela "0:00 / 0:00" dikhata
    public function voiceNoteDurationText(): ?string
    {
        if ($this->voice_note_duration_seconds === null) {
            return null;
        }

        $minutes = intdiv($this->voice_note_duration_seconds, 60);
        $seconds = $this->voice_note_duration_seconds % 60;

        return "{$minutes}:".str_pad((string) $seconds, 2, '0', STR_PAD_LEFT);
    }

    // aaj ka "10:42 AM", kal ka "Yesterday, 3:15 PM", warna poori date
    public function displayTime(): string
    {
        if ($this->created_at->isToday()) {
            return $this->created_at->format('g:i A');
        }

        if ($this->created_at->isYesterday()) {
            return 'Yesterday, '.$this->created_at->format('g:i A');
        }

        return $this->created_at->format('j M, g:i A');
    }

    // inbox mein last message ki line - photo ya voice note pe body khali hoti hai
    public function previewText(): string
    {
        return match (true) {
            (bool) $this->body => Str::limit($this->body, 60),
            (bool) $this->image_path => 'Photo',
            (bool) $this->voice_note_path => 'Voice note',
            default => '',
        };
    }
}
