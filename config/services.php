<?php

// Layanan pihak ketiga milik MISTBORNE; layanan bawaan Laravel (Postmark, SES, dll.) digabung otomatis.
return [
    // Rating Google dashboard dan hotel (Places API New). Kosong = kartu menampilkan "belum tersambung".
    'google_places' => [
        'api_key' => env('GOOGLE_PLACES_API_KEY'),
        'place_id' => env('GOOGLE_PLACES_PLACE_ID'),
    ],
];
