<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\StatusPenginapan;
use App\Enums\StatusPesananKustom;
use App\Enums\SumberPesanan;
use App\Models\Artikel;
use App\Models\Pengaturan;
use App\Models\PenggantianJadwal;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use App\Models\PesananKustom;
use App\Models\Ulasan;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data contoh bertanda "(Contoh)" untuk fitur yang tabelnya kosong (artikel draf, pesanan kustom, pesanan selesai, ulasan).
 * Tidak tampil di publik dan tidak memengaruhi kuota/KPI. Jalankan manual: php artisan db:seed --class=DataContohSeeder.
 */
class DataContohSeeder extends Seeder
{
    public function run(): void
    {
        $this->buatArtikel();
        $this->buatPesananKustomDenganRiwayat();
        $this->buatPesananSelesaiDenganUlasanDanGantiJadwal();
    }

    private function buatArtikel(): void
    {
        $judul = 'Tips Memotret Sunrise di Dieng Agar Tidak Gagal (Contoh)';

        if (Artikel::query()->withTrashed()->where('judul', $judul)->exists()) {
            return;
        }

        Artikel::query()->create([
            'judul' => $judul,
            'kategori' => 'Tips & Panduan',
            'penulis' => 'Tim MISTBORNE',
            'ringkasan' => 'Cuaca Dieng cepat berubah. Siapkan waktu, perlengkapan, dan titik pandang sebelum fajar supaya foto matahari terbit tetap tajam.',
            'isi' => '<h2>Datang lebih awal</h2><p>Matahari terbit di dataran tinggi Dieng muncul sekitar pukul 05.30. Berangkat dari penginapan sejam sebelumnya agar sempat memilih titik pandang dan menyiapkan kamera.</p>'
                .'<h2>Perlengkapan yang membantu</h2><ul><li>Jaket tebal dan sarung tangan, karena suhu bisa mendekati 5 derajat Celsius.</li><li>Tripod kecil supaya foto tidak buram saat cahaya masih redup.</li><li>Kain lembut untuk mengelap lensa dari embun.</li></ul>'
                .'<h2>Atur kamera</h2><p>Pakai ISO rendah, bukaan sedang, dan ambil beberapa foto dengan pencahayaan berbeda. Hasil terbaik biasanya muncul tepat sebelum matahari menyentuh punggung bukit.</p>',
            'img' => '/images/dieng-potret.jpg',
            'published_at' => null,
        ]);
    }

    private function buatPesananKustomDenganRiwayat(): void
    {
        if (PesananKustom::query()->withTrashed()->where('nama', 'Rina Wulandari (Contoh)')->exists()) {
            return;
        }

        $kustom = (new PesananKustom)->forceFill([
            'nama' => 'Rina Wulandari (Contoh)',
            'whatsapp' => '6281200000101',
            'destinasi_diinginkan' => 'Dieng, Bromo, dan Ijen (overland 4 hari)',
            'jadwal_diinginkan' => 'Libur akhir tahun',
            'tanggal_diinginkan' => now()->addDays(75)->toDateString(),
            'tanggal_fleksibel' => false,
            'durasi_hari' => 4,
            'jumlah_orang' => 4,
            'lokasi_penjemputan' => 'Stasiun Yogyakarta',
            'status_penginapan' => StatusPenginapan::Sudah,
            'nama_hotel' => 'Hotel Contoh Yogyakarta',
            'minat_perjalanan' => ['alam', 'fotografi'],
            'tempo_perjalanan' => 'santai',
            'catatan' => 'Ingin sunrise di Bromo dan waktu bebas untuk memotret. Ada satu peserta yang tidak kuat berjalan jauh.',
            'rencana_perjalanan' => "Hari 1: Yogyakarta ke Dieng, sore di Telaga Warna.\nHari 2: Sunrise Sikunir, lanjut ke Malang.\nHari 3: Sunrise Bromo dan kawasan Penanjakan.\nHari 4: Kawah Ijen pagi hari, kembali ke stasiun.",
            'rincian_penawaran' => 'Termasuk mobil dan sopir, pemandu, tiket masuk, dan dokumentasi. Belum termasuk penginapan.',
            'harga_final' => 3_500_000,
            'batas_penawaran' => now()->addDays(10)->toDateString(),
            'nama_pendamping' => 'Guide Uji Satu',
            'status' => StatusPesananKustom::MenungguJawaban,
            'penawaran_dikirim_pada' => now()->subDay(),
            'catatan_admin' => 'Data contoh. Hapus bila tidak diperlukan.',
        ]);
        $kustom->save();

        $admin = User::query()->orderBy('id')->first();

        $kustom->riwayatPenawaran()->create([
            'user_id' => $admin?->id,
            'versi' => 1,
            'terkirim_pada' => now()->subDay(),
            'snapshot' => [
                'nama' => $kustom->nama,
                'whatsapp' => $kustom->whatsapp,
                'destinasi' => $kustom->destinasi_diinginkan,
                'jadwal' => $kustom->jadwalDiinginkanFormat(),
                'durasi_hari' => $kustom->durasi_hari,
                'jumlah_orang' => $kustom->jumlah_orang,
                'lokasi_penjemputan' => $kustom->lokasi_penjemputan,
                'minat_perjalanan' => $kustom->minat_perjalanan,
                'tempo_perjalanan' => $kustom->tempo_perjalanan,
                'catatan_tamu' => $kustom->catatan,
                'rencana_perjalanan' => $kustom->rencana_perjalanan,
                'harga_per_orang' => $kustom->harga_final,
                'harga_total' => $kustom->hargaTotal(),
                'rincian_penawaran' => $kustom->rincian_penawaran,
                'batas_penawaran' => $kustom->batas_penawaran?->toDateString(),
            ],
        ]);
    }

    private function buatPesananSelesaiDenganUlasanDanGantiJadwal(): void
    {
        if (Pesanan::query()->where('nama_lengkap', 'Dewi Anggraini (Contoh)')->exists()) {
            return;
        }

        $perjalanan = Perjalanan::query()->where('slug', 'dieng-highland-escape')->first()
            ?? Perjalanan::query()->journey()->whereNotNull('harga')->orderBy('id')->firstOrFail();
        $harga = (int) $perjalanan->harga;
        $charge = (int) round($harga * Pengaturan::current()->persen_charge_ganti_jadwal / 100);
        $tanggalLama = now()->subDays(90)->startOfDay();
        $tanggalTrip = now()->subDays(75)->startOfDay();
        $dibuat = now()->subDays(82);

        $pesanan = (new Pesanan)->forceFill([
            'perjalanan_id' => $perjalanan->id,
            'nama_perjalanan' => $perjalanan->nama,
            'tanggal_trip' => $tanggalTrip->toDateString(),
            'tanggal_selesai' => $tanggalTrip->copy()->addDay()->toDateString(),
            'durasi_hari' => 2,
            'nama_lengkap' => 'Dewi Anggraini (Contoh)',
            'whatsapp' => '6281200000102',
            'status_penginapan' => StatusPenginapan::Sudah,
            'nama_hotel' => 'Hotel Contoh Wonosobo',
            'lokasi_pickup' => 'Stasiun Wonosobo',
            'jumlah_wisatawan' => 2,
            'harga' => $harga,
            'status' => BookingStatus::Completed,
            'sumber' => SumberPesanan::Form,
            'dikonfirmasi_at' => now()->subDays(81),
            'pembayaran_diterima_at' => now()->subDays(80),
            'nominal_dibayar' => $harga,
            'metode_pembayaran' => 'transfer',
            'tanggal_lama' => $tanggalLama->toDateString(),
            'charge_ganti_jadwal' => $charge,
            'catatan_admin' => 'Data contoh. Hapus bila tidak diperlukan.',
            'created_at' => $dibuat,
            'updated_at' => $dibuat,
        ]);
        $pesanan->save();

        (new PenggantianJadwal)->forceFill([
            'pesanan_id' => $pesanan->id,
            'tanggal_lama' => $tanggalLama->toDateString(),
            'tanggal_baru' => $tanggalTrip->toDateString(),
            'charge' => $charge,
            'created_at' => $dibuat->copy()->addDay(),
            'updated_at' => $dibuat->copy()->addDay(),
        ])->save();

        (new Ulasan)->forceFill([
            'pesanan_id' => $pesanan->id,
            'rating' => 5,
            'komentar' => 'Perjalanannya tertata rapi. Pemandu sabar dan tahu titik foto terbaik, jemputan tepat waktu. Sunrise di Sikunir benar-benar sepadan dengan dinginnya.',
            'izin_tampil' => true,
            'disetujui' => false,
            'created_at' => now()->subDays(70),
            'updated_at' => now()->subDays(70),
        ])->save();
    }
}
