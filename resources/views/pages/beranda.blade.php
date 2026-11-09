@php
    // TODO: ganti dengan data bisnis asli sebelum online (alamat/telepon/email/koordinat di desain masih contoh)
    // TODO: ganti domain di robots.txt, sitemap.xml, dan canonical saat domain produksi siap
    // TODO: set $includeOffers = true setelah harga final; harga harus sama dengan yang tampil
    $includeOffers = false;

    $pageLang = ['id' => 'id', 'en' => 'en', 'ms' => 'ms', 'zh' => 'zh-Hans', 'ja' => 'ja', 'ko' => 'ko', 'es' => 'es', 'fr' => 'fr', 'ar' => 'ar'][app()->getLocale()] ?? 'id';

    // Detail tiap journey (tagline, highlight, itinerary, info praktis) untuk modal "Lihat Detail".
    $included = array_map(fn (string $butir): string => __($butir), config('mistborne.termasuk'));
    $excluded = array_map(fn (string $butir): string => __($butir), config('mistborne.tidak_termasuk'));

    // Data journey dari tabel perjalanan (Perjalanan::kePublik()); JSON-LD TouristTrip mengikuti urutan kartu.
    $allJourneys = \App\Models\Perjalanan::query()->with('kendaraan')->journey()->where('template_pesanan_kustom', false)->orderBy('urutan')->get()->map->kePublik()->all();
    $journeys = array_values(array_filter($allJourneys, fn ($j) => $j['home'] ?? true));
    $ldJourneys = [...$journeys, ...array_values(array_filter($allJourneys, fn ($j) => ! ($j['home'] ?? true)))];

    // Daftar Termasuk/Tidak termasuk dipakai bersama semua journey di modal "Lihat Detail".
    foreach ($journeys as $i => $journey) {
        $journeys[$i]['included'] = $included;
        $journeys[$i]['excluded'] = $excluded;
    }

    $business = [
        '@id' => url('/').'#business',
        '@type' => 'TravelAgency',
        'name' => 'MISTBORNE — Private Highland Travel',
        'alternateName' => 'MISTBORNE',
        'description' => __('Private trip eksklusif ke dataran tinggi Dieng, Wonosobo, Kaligua, dan Bromo. Satu booking untuk satu kelompok dengan sopir lokal berpengalaman.'),
        'url' => url('/'),
        'telephone' => '+62 812-3456-7890',
        'email' => 'concierge@mistborne.co.id',
        'priceRange' => 'Rp 1.400.000 - Rp 4.250.000',
        'currenciesAccepted' => 'IDR',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => __('Jl. Raya Dieng KM 7, Garung'),
            'addressLocality' => 'Wonosobo',
            'addressRegion' => __('Jawa Tengah'),
            'postalCode' => '56353',
            'addressCountry' => 'ID',
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => -7.35,
            'longitude' => 109.9,
        ],
        'openingHoursSpecification' => [
            [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                'opens' => '00:00',
                'closes' => '23:59',
            ],
        ],
        'areaServed' => collect([__('Wonosobo'), __('Dieng'), __('Semarang'), __('Yogyakarta'), __('Bromo'), __('Ijen')])
            ->map(fn ($name) => ['@type' => 'AdministrativeArea', 'name' => $name])->values()->all(),
        // TODO: bahasa layanan concierge dikonfirmasi user sebelum ditambah
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'contactType' => 'reservations',
            'telephone' => '+62 812-3456-7890',
            'email' => 'concierge@mistborne.co.id',
            'availableLanguage' => ['Indonesian'],
        ],            'hasOfferCatalog' => [
            '@type' => 'OfferCatalog',
            'name' => __('Perjalanan Populer'),
            'itemListElement' => collect($ldJourneys)->map(function ($journey, $i) use ($includeOffers) {
                $place = (string) ($journey['place'] ?? '');
                $trip = [
                    '@type' => 'TouristTrip',
                    '@id' => url('/').'#trip-'.\Illuminate\Support\Str::slug($journey['name']),
                    'name' => $journey['name'],
                    'description' => collect($journey['specs'])->map(fn ($spec) => $spec['text'])->implode(' · '),
                    'url' => url('/').'#journeys',
                    'itinerary' => [
                        '@type' => 'ItemList',
                        'itemListElement' => [str_contains($place, ', ')
                            ? ['@type' => 'Place', 'name' => trim(\Illuminate\Support\Str::before($place, ', ')), 'containedInPlace' => ['@type' => 'AdministrativeArea', 'name' => trim(\Illuminate\Support\Str::after($place, ', '))]]
                            : ['@type' => 'Place', 'name' => $place],
                        ],
                    ],
                ];
                if (isset($journey['desc'])) {
                    $trip['description'] = $journey['desc'];
                }
                if (file_exists(public_path(ltrim($journey['img'], '/')))) {
                    $trip['image'] = asset($journey['img']);
                }
                $offer = ['@type' => 'Offer', 'itemOffered' => $trip];
                if ($includeOffers && is_string($journey['price'])) {
                    $offer['priceCurrency'] = 'IDR';
                    $offer['price'] = preg_replace('/[^\d]/', '', $journey['price']);
                }

                return $offer;
            })->values()->all(),
        ],
    ];

    $website = [
        '@id' => url('/').'#website',
        '@type' => 'WebSite',
        'name' => 'MISTBORNE — Private Highland Travel',
        'url' => url('/'),
        'inLanguage' => $pageLang,
        'publisher' => ['@id' => url('/').'#business'],
    ];

    $webpage = [
        '@id' => url()->current().'#webpage',
        '@type' => 'WebPage',
        'url' => url()->current(),
        'name' => __('MISTBORNE — Private Highland Travel ke Dieng & Wonosobo'),
        'description' => __('Private trip eksklusif ke dataran tinggi Dieng, Wonosobo, Kaligua, dan Bromo. Satu booking untuk satu kelompok dengan sopir lokal berpengalaman. Rute fleksibel.'),
        'inLanguage' => $pageLang,
        'isPartOf' => ['@id' => url('/').'#website'],
        'about' => ['@id' => url('/').'#business'],
    ];
    if (file_exists(public_path('images/og-image-dieng.jpg'))) {
        $webpage['primaryImageOfPage'] = [
            '@type' => 'ImageObject',
            'url' => asset('images/og-image-dieng.jpg'),
            'width' => 1200,
            'height' => 630,
        ];
    }

    $graph = [
        '@context' => 'https://schema.org',
        '@graph' => [$website, $webpage, $business],
    ];
@endphp
<x-tata-letak.aplikasi :footer-menempel="true">
    {{-- JSON-LD graph dikirim ke <head> lewat stack (bukan sebelum <!DOCTYPE>):
         konten sebelum doctype membuat parser HTML salah mode dan atribut hilang --}}
    @push('head')
        <x-seo.json-ld :data="$graph" />
    @endpush

    <x-bagian.beranda />
    <x-bagian.statistik />
    <x-bagian.destinasi />
    <x-bagian.journey-populer :journeys="$journeys" />
    <x-bagian.paket />
    <x-bagian.tentang-kami />
    <x-bagian.galeri />
    {{-- Teaser "MOMEN DI DATARAN TINGGI": tepat sebelum "Private Trip, Bukan
         Sekadar Perjalanan" (why), di bawah tombol "Lihat Semua Foto" gallery. --}}
    <x-bagian.galeri-teaser />
    <x-bagian.keunggulan />
    <x-bagian.promo-aplikasi />
    <x-bagian.testimoni />
    <x-bagian.artikel />
    <x-bagian.pertanyaan-umum />
    <x-bagian.kontak />
</x-tata-letak.aplikasi>
