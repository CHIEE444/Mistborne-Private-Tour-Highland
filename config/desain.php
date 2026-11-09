<?php

/*
 * Token desain situs (warna, font, ukuran, tema) — satu sumber, dipanggil lewat warna(), ukuran(), font(), desain().
 * Tambah warna baru di sini, bukan hex baru di markup.
 */

return [

    // ── WARNA ───────────────────────────────────────────────────────────────
    'warna' => [
        // Hijau merek (terang)
        'latarHalaman' => '#F7F8F4',   // latar utama situs
        'teksUtama' => '#14231B',      // teks tubuh
        'hijauTua' => '#12301F',       // hijau tua merek (section, tombol, judul)
        'hijauTuaTeks' => '#14301F',   // judul hijau tua khusus section
        'hijauGelap' => '#0B1F14',     // overlay gradien gelap
        'hijauMalam' => '#0E1812',     // dasar gradien kartu paket
        'hijauMalamTua' => '#0C1A12',   // ujung gradien kartu (paling gelap)
        'hijauPekat' => '#1B2E22',      // hijau pekat elemen melayang
        'hijauArang' => '#1A2820',     // latar gelap statistik
        'hijauHutan' => '#1B422B',     // hijau gradien/tombol hover
        'hijauSedang' => '#1F4D36',    // hijau kategori & label
        'hijauFokus' => '#123C2B',     // fokus input form
        'hijauKabut' => '#486553',     // teks sekunder hijau kabut
        'hijauPucat' => '#EBEFE9',     // latar pucat kehijauan
        'hijauAksi' => '#1D7A56',      // tombol aksi (Masuk)
        'hijauAksiTua' => '#175F44',   // hover tombol aksi
        'pilihLatar' => '#C8E7D1',     // latar teks terpilih (selection) body

        // Emas & krem
        'emas' => '#C8A24A',           // aksen emas utama
        'emasTua' => '#B8955A',        // emas pekat (satuan statistik)
        'emasKuning' => '#A67C37',     // emas kuning (aksen kecil)
        'emasMuda' => '#C5A059',       // emas muda (bendera/kartu)
        'emasTerang' => '#E0B85A',     // emas terang (footer)
        'krem' => '#E8D7B5',           // krem teks di atas gelap
        'kremMuda' => '#F6F1E2',       // latar krem gradien section
        'kremPucat' => '#F6F1E4',      // krem kartu paket
        'putihKrem' => '#F6F7F3',      // latar terang statistik
        'gading' => '#F8F7F2',         // latar gading section
        'kremLangsat' => '#E9E0C9',    // krem langsat (kartu artikel)
        'kremEmas' => '#D9C28F',       // krem keemasan (aksen kartu)

        // Netral
        'putih' => '#FFFFFF',
        'putihHijau' => '#F1F5EF',     // putih kehijauan (hover CTA)
        'putihHangat' => '#FBFBF8',    // putih hangat
        'abuMuda' => '#9A9A9A',        // placeholder/teks redup
        'abuTeks' => '#7C8A80',        // teks sekunder netral
        'abuTeksLusa' => '#5F6B63',    // teks sekunder sedikit gelap
        'abuTeksSedang' => '#5C635A',   // teks kartu netral sedang
        'abuTeksTerang' => '#6F766C',   // teks kartu netral terang
        'abuMudaKartu' => '#E5E9E3',    // latar avatar/ikon muda
        'abuTeksTua' => '#55605C',     // teks netral agak gelap
        'abuGaris' => '#C9CCC4',       // garis tipis di atas kolom
        'abuGarisMuda' => '#C2C8C1',   // garis pemisah muda
        'abuBorder' => '#DFE3D8',      // border kartu/form
        'abuBorderMuda' => '#E3E5E1',  // border tab/filter
        'abuBorderTips' => '#DDDDD7',  // border input form
        'abuTerang' => '#EDEDED',      // latar netral terang
        'abuPucat' => '#F2F2F0',       // latar netral pucat
        'gelap' => '#1B1B1B',          // teks gelap netral
        'hitamLunak' => '#111111',     // teks/dot hitam lunak (testimoni)
        'arang' => '#2B2B2B',          // teks arang (label form)

        // Khusus
        'waHijau' => '#25D366',        // ikon WhatsApp
        'teksGelap' => '#14272B',      // judul gelap section artikel/testimoni
        'jinggaBintang' => '#E8A020',  // bintang rating
        'ijenUngu' => '#3B4540',       // netral kehijauan tua

        // Admin panel — latar & garis
        'adminLatarSisi' => '#12301F',       // latar bilah samping admin
        'adminLatarSisiAktif' => '#1B422B',  // latar item menu aktif di bilah samping
        'adminLatarKonten' => '#FFFFFF',     // latar putih area konten admin

        // Admin panel — lencana status pesanan (bg lembut + teks)
        'statusDihubungiBg' => '#E7F0FB',
        'statusDihubungiTeks' => '#2563AA',
        'statusPaymentBg' => '#FDF2DA',
        'statusPaymentTeks' => '#A9790A',
        'statusHoldScheduleBg' => '#F1E9FB',
        'statusHoldScheduleTeks' => '#6B3FA0',
        'statusCompletedBg' => '#E3F3E6',
        'statusCompletedTeks' => '#1F7A3C',
        'statusDibatalkanBg' => '#FBE7E8',
        'statusDibatalkanTeks' => '#C0314A',

        // Admin panel — Halaman Masuk (kartu mengambang)
        'putihMasuk' => '#FCFCFC',   // latar kartu putih sangat lembut
        'borderMasuk' => '#F2F1F6',  // border input sangat tipis
    ],

    // ── FONT (satu keluarga Montserrat + fallback CJK/Arab — lihat theme.css) ──
    'font' => [
        'sans' => "Montserrat, 'Noto Sans Arabic', 'Geeza Pro', 'Segoe UI', Tahoma, sans-serif",
        'serif' => "Montserrat, 'Noto Sans Arabic', 'Geeza Pro', 'Segoe UI', Tahoma, sans-serif",
    ],

    // ── UKURAN & POSISI ─────────────────────────────────────────────────────
    'ukuran' => [
        'lebarMaks' => '1240px',       // lebar maksimum konten/navbar
        'lebarKonten' => '1200px',     // lebar maksimum konten section
        'tinggiBarNavbar' => '72px',   // tinggi baris dalam batang navbar
        'radiusBatang' => '24px',      // radius batang navbar kaca
        'radiusKartu' => '16px',       // radius kartu gambar
        'radiusPil' => '9999px',       // radius tombol pil
        'px9' => '9px',                // teks mikro kartu
        'px10' => '10px',              // teks label kecil
        'px11' => '11px',              // teks label kartu
        'px13' => '13px',              // teks kecil kartu
        'px15' => '15px',              // teks isi kartu
        'px22' => '22px',              // tinggi ikon/teks menengah
        'lebarKartuJourney' => '480px', // lebar kartu journey

        // Halaman Masuk (login) admin
        'radiusKartuMasuk' => '20px',   // radius kartu putih mengambang
        'radiusPanelFoto' => '22px',    // radius panel foto kiri di dalam kartu
    ],

    // ── BAYANGAN ────────────────────────────────────────────────────────────
    'bayangan' => [
        'kartu' => '0 20px 48px rgba(20, 35, 27, 0.12)',
        'dialog' => '0 16px 40px -12px rgba(0, 0, 0, 0.45)',
        'timbal' => '0 24px 60px -24px rgba(10, 26, 18, 0.55)',
    ],

    // ── TEMA (situs selalu terang — dark mode dihapus) ─────────────────────
    'tema' => [
        'terang' => [
            'latar' => '#F7F8F4',
            'teks' => '#14231B',
            'kartu' => '#FFFFFF',
            'border' => '#DFE3D8',
            'aksen' => '#C8A24A',
        ],
    ],
];
