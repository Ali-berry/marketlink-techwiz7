<?php

namespace App\Helpers;

// Prompt mein mana hai phir bhi model kabhi **bold** ya # heading bhej deta hai,
// aur chat bubble plain text hai - is liye yahan saaf kar dete hain
class MarkdownStripper
{
    public static function strip(string $text): string
    {
        // "* item" bullets pehle, warna italics wala pattern akela "*" pakar leta
        $text = preg_replace('/^[ \t]*\*[ \t]+/m', '- ', $text);

        // **bold** ya __bold__ - lookarounds se delimiter ke andar space nahi aa sakti,
        // taake alag alag asterisks aapas mein match na hon
        $text = preg_replace('/\*\*(?=\S)(.+?)(?<=\S)\*\*/s', '$1', $text);
        $text = preg_replace('/__(?=\S)(.+?)(?<=\S)__/s', '$1', $text);

        // *italic* ya _italic_, ek line mein. "10 kg * 2" jaisa akela asterisk match nahi hota
        $text = preg_replace('/\*(?=\S)([^*\n]+?)(?<=\S)\*/', '$1', $text);
        $text = preg_replace('/(?<![a-zA-Z0-9])_(?=\S)([^_\n]+?)(?<=\S)_(?![a-zA-Z0-9])/', '$1', $text);

        // "# " headings sirf line ke shuru mein, "Order #123" ko nahi chhedte
        $text = preg_replace('/^[ \t]*#{1,6}[ \t]+/m', '', $text);

        // chat reply mein backtick ki zaroorat hi nahi
        $text = str_replace('`', '', $text);

        return $text;
    }
}
