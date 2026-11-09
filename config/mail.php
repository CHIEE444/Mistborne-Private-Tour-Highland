<?php

// Hanya nilai yang berbeda dari bawaan Laravel: nama pengirim jatuh ke APP_NAME bila MAIL_FROM_NAME kosong.
return [
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel')),
    ],
];
