<?php

/*
 * Konfigurasi data tetap MISTBORNE yang tidak punya tabel sendiri di database.
 * Nilai aturan bisnis (kuota, charge ganti jadwal, batas bayar, dsb.) TIDAK di sini —
 * itu ada di tabel `pengaturan` (satu baris, lihat App\Models\Pengaturan::current()).
 */

return [

    // Akun admin tunggal yang dibuat AdminSeeder. Registrasi publik dimatikan,
    // satu-satunya cara masuk admin panel adalah lewat akun ini.
    'admin' => [
        'name' => env('ADMIN_SEED_NAME', 'Admin MISTBORNE'),
        'email' => env('ADMIN_SEED_EMAIL', 'admin@mistborne.id'),
        'password' => env('ADMIN_SEED_PASSWORD', 'password'),
    ],

    // Rekening resmi yang disebut di pesan WhatsApp konfirmasi pembayaran (D7).
    // Tidak bisa diedit dari UI — perubahan lewat file ini.
    'rekening' => [
        ['bank' => 'BCA', 'nomor' => '1234567890', 'atas_nama' => 'PT MISTBORNE HIGHLAND TRAVEL'],
    ],

    // Saran kategori Artikel — hanya titik awal. Admin bebas mengetik kategori
    // baru; form juga menyarankan kategori yang sudah pernah dipakai artikel lain.
    'kategori_artikel' => [
        'Tips & Panduan',
        'Destinasi',
        'Budaya & Cerita',
        'Kuliner',
        'Gaya Hidup',
        'Berita & Pengumuman',
    ],

    // Rincian harga yang sama untuk semua journey: tampil di halaman detail journey.
    // Teks diterjemahkan saat ditampilkan (lang/*.json).
    'termasuk' => [
        'Pemandu lokal dari MISTBORNE yang sekaligus menjadi sopir privat berpengalaman',
        'Transportasi selama trip, dan parkir',
        'Bahan bakar (BBM) kendaraan',
        'Penginapan minimal bintang 4 (maks. 4 orang per kamar)',
        'Tiket masuk objek wisata',
        'Air mineral, selimut hangat, dan charger',
    ],
    'tidak_termasuk' => [
        'Makan dan minum',
        'Oleh-oleh',
        'Tip',
        'Aktivitas opsional di luar itinerary',
        'Pengeluaran pribadi',
    ],

    // Kapasitas dihitung untuk TAMU saja: guide MISTBORNE merangkap driver dan memakai 1 kursi.
    // Contoh: kendaraan 6 kursi = guide/driver + 5 tamu (batas standar journey tetap 4 tamu).
    'kursi_staf' => 1,
    'tamu_maks_standar' => 4,

    // Pilihan armada untuk tamu TIDAK lagi di sini: dibaca dari menu Kendaraan di admin panel (App\Bantuan\PilihanKendaraan),
    // supaya formulir tamu dan data armada admin selalu sama. 'maks_tamu' = kapasitas kursi unit dikurangi 'kursi_staf'.

    // Cadangan database (php artisan database:cadangkan). Folder berada di disk "local" (storage/app/private).
    // pg_dump kosong = dicari otomatis (PATH, Homebrew, /Library/PostgreSQL); isi PG_DUMP_PATH bila tidak ketemu.
    'cadangan' => [
        'folder' => 'cadangan',
        'koneksi' => env('BACKUP_DB_CONNECTION'), // kosong = koneksi database utama
        'simpan_hari' => (int) env('BACKUP_SIMPAN_HARI', 14),
        'minimal_disimpan' => 3,
        'pg_dump' => env('PG_DUMP_PATH'),
    ],

    // Kontak uji coba. Selama masa testing, semua hotel dan guide memakai nomor/email ini supaya mudah dicoba.
    // Ganti nomor dan email tiap hotel/guide di admin panel menjelang rilis.
    'kontak_uji' => [
        'whatsapp' => '081808606086',
        'email' => 'razifarba4@gmail.com',
    ],

    // Kata sandi awal akun Panel Guide yang dibuat admin, supaya guide langsung bisa masuk tanpa tautan atur sandi.
    // Hanya untuk masa uji coba; ganti lewat .env sebelum dipakai sungguhan.
    'sandi_bawaan_guide' => env('MISTBORNE_SANDI_BAWAAN_GUIDE', 'password'),

    // Skema pembayaran yang ditampilkan di detail journey. Persentase DP dan batas pelunasan
    // bersifat kebijakan; ganti angka di sini bila kebijakan berubah.
    // dp_persen = DP minimal (sama dengan uang muka minimal 30% yang diwajibkan sistem untuk status Payment).
    'pembayaran' => [
        'dp_persen' => 30,
        'pelunasan_hari_sebelum' => 3,
        // Pesanan yang sudah deal tapi belum transfer: lewat batas bayar (Pengaturan, bawaan 3 hari) muncul peringatan
        // di dashboard; tenggang ini kemudian membatalkannya otomatis (3 + 2 = hari ke-5 setelah konfirmasi).
        'tenggang_batal_hari' => 2,
    ],

    // Ketentuan umum yang tampil di detail journey (":persen" = biaya ganti jadwal dari menu Pengaturan).
    'ketentuan' => [
        'Pembayaran lewat transfer bank ke rekening resmi MISTBORNE; kirim bukti transfer ke admin.',
        'Pembatalan H-7 atau lebih: gratis dan dana kembali 100%. H-6 sampai H-2: dana kembali 50%. H-1 atau kurang: tidak ada pengembalian dana.',
        'Ganti jadwal hanya bisa lebih dari 7 hari sebelum keberangkatan; penggantian pertama dikenai biaya :persen% dari harga, berikutnya gratis.',
    ],

    // Dasar perhitungan harga journey/paket. Komponennya: sewa mobil, BBM, sewa guide yang merangkap sopir, tol/parkir,
    // hotel, tiket, dan keuntungan. Dipakai HargaPerjalananLayanan. Rincian hotel dan tiket tiap journey ada di
    // resources/data/biaya-perjalanan.php. Harga final tetap bisa diubah admin di menu Journey/Paket Wisata.
    'biaya_perjalanan' => [
        'sewa_mobil_per_hari' => 800_000,
        'sewa_guide_per_hari' => 350_000,
        'tol_parkir_per_hari' => 75_000,
        'makan_inap_guide_per_malam' => 200_000,
        'bbm_per_km' => 1_300,
        // All-inclusive: penginapan bintang 4 (4 tamu per kamar) dan tiket masuk tiap tamu ikut dihitung.
        'penginapan_per_kamar_per_malam' => 650_000,
        'tamu_per_kamar' => 4,
        'tamu_standar' => 4,
        // Keuntungan MISTBORNE sekitar 10%, tetapi harga jual tidak pernah lebih dari Rp500.000 di atas biaya pokok.
        'margin_persen' => 10,
        'margin_maks' => 499_999,
        'pembulatan' => 50_000,
    ],

];
