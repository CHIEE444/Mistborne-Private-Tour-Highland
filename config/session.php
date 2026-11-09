<?php

use Illuminate\Support\Str;

// Hanya nilai yang berbeda dari bawaan Laravel.
return [
    'cookie' => env('SESSION_COOKIE', Str::slug((string) env('APP_NAME', 'laravel')).'-session'),

    // Data sesi disimpan sebagai JSON, bukan objek PHP (lebih aman bila APP_KEY bocor).
    'serialization' => 'json',
];
