<?php

// Hanya nilai yang berbeda dari bawaan Laravel: kunci (lock) cache file disimpan terpisah dari datanya.
return [
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/locks'),
        ],
    ],

    // Cache tidak boleh membongkar objek PHP (mencegah serangan "gadget chain" bila APP_KEY bocor).
    'serializable_classes' => false,
];
