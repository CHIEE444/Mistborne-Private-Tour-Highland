<?php

/**
 * Biaya pokok per journey/paket untuk HargaPerjalananLayanan: km (termasuk antar-jemput), penginapan per malam
 * (kamar keluarga 4 tamu), tiket per orang (tarif hari libur), dan biaya grup. Tiket resmi dicek Oktober 2026
 * (Bromo/Ijen PP 36/2024, Borobudur, Kawah Putih, Tangkuban Parahu, Green Canyon, Papandayan); sisanya perkiraan.
 *
 * @return array<string, array{km: int, penginapan: list<array{0: string, 1: int}>, tiket: list<array{0: string, 1: int}>, grup?: list<array{0: string, 1: int}>}>
 */
return [
    'DIENG HIGHLAND ESCAPE' => [
        'km' => 330,
        'penginapan' => [['Artotel Wanasaba, Grand Suite', 1_150_000], ['Artotel Wanasaba, Grand Suite', 1_150_000]],
        'tiket' => [['Kawah Sikidang & Candi Arjuna (terusan)', 30_000], ['Telaga Warna & Pengilon', 25_000], ['Bukit Sikunir', 15_000], ['Kebun teh Tambi', 15_000], ['Kaligua', 20_000]],
    ],
    'BROMO SUNRISE JOURNEY' => [
        'km' => 380,
        'penginapan' => [['The Singhasari Resort (Batu), Family Room', 2_200_000], ['Hotel bintang 4 kawasan Bromo', 1_100_000], ['Hotel bintang 4 Kota Malang', 1_150_000]],
        'tiket' => [['Museum Angkut', 130_000], ['Coban Rondo', 30_000], ['Kawasan Bromo TNBTS (hari libur)', 79_000], ['Air Terjun Madakaripura', 20_000], ['Kampung Jodipan & Tridi', 10_000]],
        'grup' => [['Jip sunrise Penanjakan, lautan pasir, dan kawah', 850_000]],
    ],
    'IJEN VOLCANIC ESCAPE' => [
        'km' => 330,
        'penginapan' => [['Aston Banyuwangi, Family Room', 1_000_000], ['Aston Banyuwangi, Family Room', 1_000_000]],
        'tiket' => [['Taman Nasional Baluran', 30_000], ['TWA Kawah Ijen (hari libur)', 30_000], ['Pantai Pulau Merah', 10_000], ['Desa adat Osing Kemiren', 10_000]],
        'grup' => [['Sewa masker gas & senter kepala', 120_000]],
    ],
    'CIWIDEY & RANCABALI ESCAPE' => [
        'km' => 250,
        'penginapan' => [['Forest Hills Ciwidey, Family Room', 1_000_000], ['Forest Hills Ciwidey, Family Room', 1_000_000]],
        'tiket' => [['Kawah Putih + shuttle ontang-anting', 48_000], ['Situ Patenggang', 25_000], ['Kebun teh Rancabali', 10_000], ['Ranca Upas', 20_000], ['Petik strawberry', 30_000], ['Tangkuban Parahu (hari libur)', 30_000], ['Floating Market Lembang', 30_000]],
    ],
    'GREEN CANYON JOURNEY' => [
        'km' => 180,
        'penginapan' => [['Menara Laut Hotel, Family Room', 1_100_000], ['Menara Laut Hotel, Family Room', 1_100_000]],
        'tiket' => [['Green Canyon (Cukang Taneuh)', 10_000], ['Kawasan Pantai Pangandaran', 20_000], ['Pantai Batu Karas', 15_000], ['Pantai Batu Hiu', 10_000], ['Cagar Alam Pananjung', 25_000]],
        'grup' => [['Perahu Green Canyon (per kapal)', 200_000], ['Perahu ke Pantai Pasir Putih', 150_000]],
    ],
    'PAPANDAYAN CRATER JOURNEY' => [
        'km' => 220,
        'penginapan' => [['Hotel Santika Premiere Garut, Family Room', 1_050_000]],
        'tiket' => [['TWA Gunung Papandayan (hari libur)', 37_500], ['Padang edelweis Tegal Alun', 0]],
    ],
    'LEMBANG & TANGKUBAN PARAHU JOURNEY' => [
        'km' => 120,
        'penginapan' => [['The Lodge Maribaya, Family Room', 1_300_000]],
        'tiket' => [['Tangkuban Parahu (hari libur)', 30_000], ['Kebun bunga & Orchid Forest Cikole', 50_000]],
    ],
    'BOROBUDUR & MERAPI SUNRISE JOURNEY' => [
        'km' => 180,
        'penginapan' => [['Atria Hotel Magelang, Family Room', 1_000_000]],
        'tiket' => [['Candi Borobudur (naik candi)', 120_000], ['Candi Mendut & Pawon', 15_000], ['Punthuk Setumbu', 30_000], ['Ketep Pass', 12_500]],
    ],
    'Kawah Rengganis' => [
        'km' => 130,
        'penginapan' => [['Forest Hills Ciwidey, Family Room', 1_000_000]],
        'tiket' => [['Kawah Rengganis', 25_000], ['Situ Patenggang', 25_000], ['Kebun teh Rancabali', 10_000]],
    ],
    'Bukit Sikunir' => [
        'km' => 280,
        'penginapan' => [['Artotel Wanasaba, Grand Suite', 1_150_000]],
        'tiket' => [['Kawah Sikidang & Candi Arjuna (terusan)', 30_000], ['Telaga Warna & Pengilon', 25_000], ['Bukit Sikunir', 15_000], ['Kebun teh Tambi', 15_000], ['Kaligua', 20_000]],
    ],
    'Telaga Sarangan & Cemoro Sewu' => [
        'km' => 150,
        'penginapan' => [['Aston Madiun, Suite', 1_000_000]],
        'tiket' => [['Telaga Sarangan', 20_000], ['Air Terjun Tirtosari', 10_000], ['Cemoro Sewu', 15_000]],
        'grup' => [['Perahu motor keliling telaga', 80_000]],
    ],
    'Hutan Pinus' => [
        'km' => 200,
        'penginapan' => [['Artotel Wanasaba, Grand Suite', 1_150_000]],
        'tiket' => [['Kawasan hutan pinus', 15_000]],
    ],
];
