<?php

namespace App\Support;

use Carbon\Carbon;

class Workspace
{
    public static function date($value): string
    {
        return $value ? Carbon::parse($value)->timezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H.i') : '—';
    }

    public static function money($value): string
    {
        return 'Rp'.number_format((float) $value, 0, ',', '.');
    }

    public static function number($value, int $places = 0): string
    {
        return number_format((float) $value, $places, ',', '.');
    }

    public static function initials(string $name): string
    {
        return mb_strtoupper(collect(preg_split('/\s+/u', trim($name)))->filter()->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->join('')) ?: 'ML';
    }

    public static function asset(string $node, string $name): ?string
    {
        static $manifest;
        $manifest ??= json_decode(file_get_contents(public_path('figma/manifest.json')), true);
        $file = $manifest[$node][$name] ?? $manifest['127-1725'][$name] ?? $manifest['127-45'][$name] ?? null;
        return $file ? asset('figma/'.$file) : null;
    }
}
