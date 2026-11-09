<?php

namespace App\Layanan;

use App\Bantuan\FormatRupiah;
use App\Enums\BookingStatus;
use App\Enums\StatusPenginapan;
use App\Enums\StatusReservasiHotel;
use App\Models\Hotel;
use App\Models\Pengaturan;
use App\Models\PermintaanPesanan;
use App\Models\Pesanan;
use App\Models\PesananKustom;

/**
 * Menyusun teks pesan WhatsApp siap kirim untuk tiap aksi admin. Teks ini yang tampil
 * di panel "Pesan WhatsApp" pada detail pesanan — admin menyalin/klik, bukan terkirim otomatis.
 */
class PesanWhatsAppLayanan
{
    public function __construct(private readonly BatasBayarLayanan $batasBayar) {}

    public function konfirmasiBayar(Pesanan $pesanan): string
    {
        $batas = $this->batasBayar->batasWaktu($pesanan);
        $totalTagihan = $pesanan->totalTagihan();
        $total = $totalTagihan !== null ? $this->rupiah($totalTagihan) : 'Belum ditentukan';
        $biayaGantiJadwal = $pesanan->totalBiayaGantiJadwal();
        $sudahDibayar = $pesanan->nominal_dibayar !== null
            ? $this->rupiah($pesanan->nominal_dibayar)
            : 'Belum ada pembayaran yang tercatat';
        $sisaTagihan = $pesanan->sisaTagihan();
        $sisa = $sisaTagihan !== null ? $this->rupiah($sisaTagihan) : 'Belum dapat dihitung';
        $instruksiBayar = $pesanan->sisaTagihan() === null
            ? 'Silakan hubungi kami untuk memastikan nominal pembayaran.'
            : ($pesanan->sisaTagihan() > 0
                ? 'Mohon lakukan pembayaran sebesar '.$sisa.' dan kirim bukti pembayaran setelah transfer.'
                : 'Pembayaran pesanan ini sudah lunas.');
        $batasTeks = match (true) {
            $pesanan->status === BookingStatus::Payment => 'Pelunasan paling lambat H-3 (tiga hari sebelum keberangkatan).',
            $batas !== null => $batas->translatedFormat('d F Y, H:i').' WIB',
            default => 'Akan diinformasikan setelah pesanan dikonfirmasi.',
        };
        $rincianPelunasan = match (true) {
            $sisaTagihan === 0 => 'Sisa Tagihan: Lunas',
            $sisaTagihan > 0 => "Sisa Tagihan: {$sisa}\nBatas Waktu Bayar: {$batasTeks}",
            default => 'Sisa Tagihan: Belum dapat dihitung',
        };
        $rekening = collect(config('mistborne.rekening', []))
            ->map(fn (array $r, int $i) => ($i + 1).". Bank {$r['bank']}: {$r['nomor']} a.n. {$r['atas_nama']}")
            ->implode("\n");
        $rekeningSection = ($pesanan->sisaTagihan() ?? 0) > 0
            ? ($rekening !== '' ? "Rekening Resmi MISTBORNE:\n{$rekening}" : 'Silakan hubungi admin untuk mendapatkan rekening resmi pembayaran.')
            : '';
        $rincianBiayaGantiJadwal = $biayaGantiJadwal > 0
            ? 'Termasuk biaya ganti jadwal: '.$this->rupiah($biayaGantiJadwal)
            : '';
        $persenDp = (int) config('mistborne.pembayaran.dp_persen', 50);
        $hariPelunasan = (int) config('mistborne.pembayaran.pelunasan_hari_sebelum', 3);
        $skemaBayar = ($sisaTagihan ?? 0) > 0 && $totalTagihan !== null
            ? "Pilihan pembayaran:\n- Lunas: bayar {$this->rupiah($totalTagihan)} sekaligus.\n- DP {$persenDp}%: bayar {$this->rupiah((int) ceil($totalTagihan * $persenDp / 100))} lebih dulu, sisanya dilunasi paling lambat H-{$hariPelunasan} sebelum keberangkatan."
            : '';
        $rincianTrip = $this->rincianTrip($pesanan);
        if ($pesanan->status_penginapan === StatusPenginapan::Belum && blank($pesanan->nama_hotel)) {
            $rincianTrip .= "\nBila Kakak memesan penginapan sendiri, konfirmasi nama dan alamat hotelnya lewat WhatsApp agar jemputan tercatat rapi:\n"
                .$this->tautanKonfirmasiWa($this->pesanPilihPenginapanSendiri($pesanan->nama_lengkap, $pesanan->kode));
        }
        $langkahBerikutnya = $sisaTagihan === 0
            ? 'Tidak ada sisa tagihan. Informasi penjemputan dan Guide (sekaligus driver) akan kami kirim menjelang keberangkatan melalui pengingat H-2.'
            : 'Setelah pembayaran diterima dan diverifikasi, kami akan melanjutkan persiapan operasional. Informasi penjemputan dan Guide (sekaligus driver) akan dikirim menjelang keberangkatan.';

        return <<<TEKS
            Halo Kak {$pesanan->nama_lengkap},

            Berikut rincian pembayaran pesanan {$pesanan->nama_perjalanan} ({$pesanan->kode}) untuk {$pesanan->jumlah_wisatawan} orang.

            Total Harga: {$total}
            {$rincianBiayaGantiJadwal}
            Sudah Dibayar: {$sudahDibayar}
            {$rincianPelunasan}

            Tanggal Keberangkatan: {$pesanan->tanggal_trip->translatedFormat('d F Y')}
            {$rincianTrip}

            {$skemaBayar}

            {$rekeningSection}

            {$instruksiBayar}

            {$langkahBerikutnya}
            Terima kasih.
            TEKS;
    }

    /** Pesanan sudah deal tetapi belum ada transfer setelah batas bayar: ingatkan tamu sebelum dibatalkan otomatis. */
    public function peringatanLewatBatasBayar(Pesanan $pesanan): string
    {
        $batasBatal = $this->batasBayar->batasPembatalan($pesanan);
        $tagihan = $pesanan->sisaTagihan();
        $rekening = collect(config('mistborne.rekening', []))
            ->map(fn (array $r, int $i) => ($i + 1).". Bank {$r['bank']}: {$r['nomor']} a.n. {$r['atas_nama']}")
            ->implode("\n");

        return collect([
            "Halo Kak {$pesanan->nama_lengkap},",
            "Kami belum menerima pembayaran untuk pesanan {$pesanan->nama_perjalanan} ({$pesanan->kode}) tanggal {$pesanan->tanggal_trip->translatedFormat('d F Y')}. Batas bayarnya sudah lewat.",
            $tagihan !== null ? 'Tagihan: '.$this->rupiah($tagihan).' (DP minimal '.(int) config('mistborne.pembayaran.dp_persen', 30).'% sudah cukup untuk mengunci jadwal).' : null,
            $rekening !== '' ? "Rekening Resmi MISTBORNE:\n{$rekening}" : null,
            $batasBatal ? 'Bila sampai '.$batasBatal->translatedFormat('d F Y').' belum ada transfer, pesanan ini dibatalkan otomatis dan kuotanya dibuka untuk tamu lain.' : null,
            'Bila sudah transfer, mohon kirim bukti pembayarannya di sini. Terima kasih.',
        ])->filter()->implode("\n\n");
    }

    /**
     * Pesan konfirmasi ke tamu setelah formulir permintaan masuk: merangkum data isian agar tamu
     * dapat memastikan atau meminta perubahan sebelum admin membuat pesanan.
     */
    public function konfirmasiPermintaan(PermintaanPesanan $permintaan): string
    {
        $nama = filled($permintaan->nama_lengkap) ? ' '.$permintaan->nama_lengkap : '';
        $jamJemput = $permintaan->waktu_pickup
            ? substr((string) $permintaan->waktu_pickup, 0, 5).' WIB'
            : 'Akan kami informasikan setelah dikonfirmasi';
        $catatan = collect([
            filled($permintaan->paket_opsional) ? "Catatan tambahan: {$permintaan->paket_opsional}" : null,
            filled($permintaan->permintaan_khusus) ? "Permintaan khusus: {$permintaan->permintaan_khusus}" : null,
        ])->filter()->implode("\n");
        $tautanPerubahan = route('form-pesanan.tampil', $permintaan->token);
        $masaBerlaku = $permintaan->token_berlaku_sampai?->translatedFormat('d F Y, H:i');
        $barisPenginapan = $this->barisPenginapan($permintaan->status_penginapan, $permintaan->nama_hotel, $permintaan->alamat_hotel);
        $tautanKonfirmasi = $this->tautanKonfirmasiWa($this->pesanKonfirmasiTamu($permintaan->nama_lengkap, $permintaan->perjalanan?->nama));

        return trim(<<<TEKS
            Halo Kak{$nama},

            Terima kasih sudah mengisi formulir MISTBORNE. Saya admin yang akan membantu memastikan detail perjalanan Kakak.

            Journey: {$permintaan->perjalanan?->nama}
            Tanggal Keberangkatan: {$permintaan->tanggal_trip?->translatedFormat('d F Y')}
            Jumlah Peserta: {$permintaan->jumlah_wisatawan} orang
            {$barisPenginapan}
            Lokasi Jemput: {$permintaan->lokasi_pickup}
            Jam Jemput: {$jamJemput}
            {$catatan}

            Mohon konfirmasi apakah data di atas sudah sesuai. Jika ingin mengganti tanggal, hotel, atau memperbarui informasi lain, silakan gunakan formulir berikut (berlaku sampai {$masaBerlaku} WIB):
            {$tautanPerubahan}

            Setelah memperbarui formulir, Kakak bisa langsung mengonfirmasi ke admin lewat WhatsApp:
            {$tautanKonfirmasi}

            Setelah Kakak setuju, kami buatkan pesanannya dan kirim rincian pembayaran. Terima kasih, Kak.
            TEKS);
    }

    public function pesananDisetujui(Pesanan $pesanan): string
    {
        $jamJemput = $pesanan->waktu_pickup
            ? substr((string) $pesanan->waktu_pickup, 0, 5).' WIB'
            : 'Akan dikonfirmasi oleh admin';

        $kendaraan = $pesanan->kendaraan?->namaUntukTamu() ?? 'Akan dikonfirmasi oleh admin';

        return <<<TEKS
            Halo Kak {$pesanan->nama_lengkap},

            Kabar baik! Permintaan perjalanan Kakak sudah disetujui dan tercatat sebagai pesanan MISTBORNE 🎉

            Kode Pesanan: {$pesanan->kode}
            Journey: {$pesanan->nama_perjalanan}
            Tanggal Perjalanan: {$pesanan->tanggal_trip->translatedFormat('d F Y')}
            Jumlah Peserta: {$pesanan->jumlah_wisatawan} orang
            {$this->barisPenginapan($pesanan->status_penginapan, $pesanan->nama_hotel, $pesanan->alamat_hotel)}
            Lokasi Penjemputan: {$pesanan->lokasi_pickup}
            Perkiraan Jam Jemput: {$jamJemput}
            Kendaraan: {$kendaraan}

            Unit kendaraan dapat berubah menyesuaikan ketersediaan; bila berubah, admin akan mengabari Kakak.

            Mohon periksa kembali rincian di atas. Jika ada yang perlu dikoreksi, balas pesan ini agar admin dapat membantu.

            Berikutnya, admin akan menghubungi Kakak mengenai informasi pembayaran dan persiapan perjalanan.

            Terima kasih sudah memilih MISTBORNE. Sampai bertemu di perjalanan!
            TEKS;
    }

    /**
     * Pesan konfirmasi ulang ke tamu setelah admin mengubah data pesanan.
     *
     * @param  list<array{label: string, lama: string, baru: string}>  $perubahan
     */
    public function perubahanData(Pesanan $pesanan, array $perubahan): string
    {
        $daftar = collect($perubahan)
            ->map(fn (array $baris): string => "- {$baris['label']}: {$baris['lama']} → {$baris['baru']}")
            ->implode("\n");

        return <<<TEKS
            Halo Kak {$pesanan->nama_lengkap},

            Admin MISTBORNE memperbarui data pesanan {$pesanan->kode} ({$pesanan->nama_perjalanan}) pada {$pesanan->tanggal_trip->translatedFormat('d F Y')}.

            Perubahan:
            {$daftar}

            Mohon periksa kembali. Jika sudah sesuai, cukup balas "Setuju" ya, Kak. Jika ada yang keliru, balas pesan ini agar segera kami perbaiki.

            Terima kasih.
            TEKS;
    }

    /** Kabar ke tamu tentang unit kendaraan yang siap menjemput dan dipakai selama perjalanan; unit sebelumnya tidak disebut. */
    public function perubahanUnit(Pesanan $pesanan, string $unitBaru): string
    {
        return <<<TEKS
            Halo Kak {$pesanan->nama_lengkap},

            Info untuk pesanan {$pesanan->kode} ({$pesanan->nama_perjalanan}) pada {$pesanan->tanggal_trip->translatedFormat('d F Y')}.

            Kendaraan untuk perjalanan Kakak sudah siap digunakan:
            • Unit: {$unitBaru}

            Unit ini yang akan menjemput Kakak dan dipakai selama perjalanan tour. Kendaraan dikemudikan guide MISTBORNE kami. Jika ada pertanyaan, balas pesan ini ya, Kak.

            Terima kasih.
            TEKS;
    }

    public function aturanPembatalan(Pesanan $pesanan): string
    {
        $persenGantiJadwal = Pengaturan::current()->persen_charge_ganti_jadwal;

        return <<<TEKS
            Halo Kak {$pesanan->nama_lengkap},

            Untuk pesanan {$pesanan->kode} ({$pesanan->nama_perjalanan}), berikut aturan pembatalan & ganti jadwal:

            - Pembatalan H-7 atau lebih sebelum keberangkatan: gratis tanpa biaya, refund 100% dari pembayaran yang sudah diterima.
            - Pembatalan H-6 sampai H-2: refund 50% dari total tagihan termasuk biaya ganti jadwal, maksimal sebesar pembayaran yang sudah diterima.
            - Pembatalan H-1 atau kurang: tidak ada refund (dana hangus).
            - Pembatalan dari pihak MISTBORNE (cuaca/bencana/kondisi khusus): refund 100% dari pembayaran yang sudah diterima.
            - Ganti jadwal setelah pembayaran (di atas H-7) dikenakan biaya {$persenGantiJadwal}% dari harga pada ganti jadwal pertama; ganti jadwal berikutnya gratis.
            - Mulai H-7, jadwal tidak dapat diganti dan sudah tetap.

            Untuk mengajukan pembatalan atau memastikan perhitungan refund, balas pesan ini agar admin dapat memeriksa tanggal dan pembayaran pesanan Anda. Waktu proses refund akan dikonfirmasi admin setelah pengajuan.

            Terima kasih sudah mempercayakan perjalanan Anda bersama kami.
            TEKS;
    }

    public function gantiJadwal(Pesanan $pesanan, int $charge): string
    {
        $jadwalTerakhir = $pesanan->penggantian->first();
        $tanggalLama = $jadwalTerakhir?->tanggal_lama ?? $pesanan->tanggal_lama;
        $tanggalBaru = $jadwalTerakhir?->tanggal_baru ?? $pesanan->tanggal_trip;
        $chargeTerakhir = $jadwalTerakhir?->charge ?? $charge;
        $barisTanggalLama = $tanggalLama
            ? "Jadwal sebelumnya: {$tanggalLama->translatedFormat('d F Y')}\n"
            : '';
        $biaya = $chargeTerakhir > 0
            ? "Biaya tambahan ganti jadwal: {$this->rupiah($chargeTerakhir)}. Admin akan mengirimkan instruksi pembayaran biaya tambahan."
            : 'Ganti jadwal ini tidak dikenakan biaya tambahan.';
        $rincianTagihan = $pesanan->totalTagihan() !== null
            ? "Total tagihan terbaru: {$this->rupiah($pesanan->totalTagihan())}\nSudah dibayar: {$this->rupiah($pesanan->nominal_dibayar ?? 0)}\nSisa tagihan: ".($pesanan->sisaTagihan() === 0 ? 'Lunas' : $this->rupiah($pesanan->sisaTagihan()))
            : 'Total tagihan akan dikonfirmasi admin.';

        return <<<TEKS
            Halo Kak {$pesanan->nama_lengkap},

            Jadwal pesanan {$pesanan->kode} ({$pesanan->nama_perjalanan}) telah diubah.
            {$barisTanggalLama}Jadwal baru: {$tanggalBaru->translatedFormat('d F Y')}.

            {$biaya}

            {$rincianTagihan}

            Kami akan mengonfirmasi kembali kesiapan Guide (sekaligus driver) untuk jadwal baru. Informasi operasional akan diperbarui setelah penugasan dikonfirmasi.

            Terima kasih atas pengertiannya.
            TEKS;
    }

    /** H-7: kunci jadwal + ringkasan SOP keberangkatan. */
    public function pengingatH7(Pesanan $pesanan): string
    {
        return <<<TEKS
            Halo Kak {$pesanan->nama_lengkap},

            Trip {$pesanan->nama_perjalanan} ({$pesanan->kode}) akan dilaksanakan tanggal {$pesanan->tanggal_trip->translatedFormat('d F Y')} (H-7).

            Keberangkatan tinggal 7 hari. Mohon cek kembali tanggal perjalanan dan perlengkapan yang dibutuhkan.

            Mulai H-7, perubahan jadwal sudah tidak dapat diajukan. Jika ada hal yang ingin ditanyakan atau kendala, silakan hubungi admin. Pembatalan tetap mengikuti ketentuan yang berlaku.

            Sampai jumpa di dataran tinggi!
            TEKS;
    }

    /** H-3: checklist perlengkapan dan kesiapan tamu sebelum keberangkatan. */
    public function pengingatH3(Pesanan $pesanan): string
    {
        $pelunasan = ($pesanan->sisaTagihan() ?? 0) > 0
            ? "Jika masih ada sisa tagihan, mohon lunasi hari ini (H-3) melalui rekening resmi MISTBORNE. Sisa tagihan: {$this->rupiah($pesanan->sisaTagihan())}.\n\n"
            : '';

        return <<<TEKS
            Halo Kak {$pesanan->nama_lengkap},

            Tiga hari lagi menuju perjalanan {$pesanan->nama_perjalanan} ({$pesanan->kode}) pada {$pesanan->tanggal_trip->translatedFormat('d F Y')}. Yuk cek persiapan sebelum berangkat:

            - Siapkan jaket hangat, pakaian ganti, dan jas hujan.
            - Gunakan sepatu yang nyaman untuk berjalan di area alam.
            - Bawa obat pribadi, identitas diri, uang tunai secukupnya, serta charger/power bank.
            - Pastikan jumlah peserta dan kebutuhan khusus rombongan sudah disampaikan kepada kami.
            - Cek kembali lokasi dan jam penjemputan bersama admin.

            {$pelunasan}Jika ada perubahan jumlah peserta, lokasi penjemputan, kondisi kesehatan, kebutuhan khusus, atau barang bawaan besar, kabari kami agar persiapan perjalanan dapat disesuaikan.

            Sampai bertemu, Kak!
            TEKS;
    }

    /**
     * H-2: detail jemputan menjelang keberangkatan. Baris guide/hotel/lokasi
     * dihilangkan kalau datanya belum diisi admin — jangan pernah mencetak baris kosong
     * menggantung (mis. "Guide: ").
     */
    public function pengingatH2(Pesanan $pesanan): string
    {
        $detail = collect([
            'Lokasi Pick-up' => $pesanan->lokasi_pickup,
            'Jam Jemput' => $pesanan->waktu_pickup ? substr((string) $pesanan->waktu_pickup, 0, 5).' WIB' : null,
            'Penginapan' => $pesanan->nama_hotel ?: match ($pesanan->status_penginapan) {
                StatusPenginapan::Belum => 'Belum diinformasikan. Mohon kabari nama penginapan Kakak agar sopir kami bisa menjemput dari sana',
                StatusPenginapan::Dipesankan => 'Hotel dipesankan MISTBORNE: detail hotel akan kami kirim terpisah',
                StatusPenginapan::Diurus => 'All-inclusive: detail hotel akan kami kirim terpisah',
                default => null,
            },
            'Guide (sekaligus driver)' => $pesanan->nama_guide,
            'Kendaraan' => $pesanan->kendaraan?->namaUntukTamu(),
        ])
            ->filter(fn (?string $nilai) => filled($nilai)
                && ! in_array(mb_strtolower(trim($nilai)), ['terserah', '-', 'belum ditentukan', 'belum diisi'], true))
            ->map(fn (string $nilai, string $label) => "{$label}: {$nilai}")
            ->push("Jumlah Peserta: {$pesanan->jumlah_wisatawan} orang")
            ->implode("\n");

        $instruksiWaktu = $pesanan->waktu_pickup
            ? 'Mohon sudah siap di lokasi 10–15 menit sebelum jam jemput. Jika titik jemput kurang jelas atau ada perubahan, balas pesan ini agar kami bantu konfirmasi.'
            : 'Jika titik jemput atau jam penjemputan belum jelas, balas pesan ini agar kami bantu konfirmasi.';

        return <<<TEKS
            Halo Kak {$pesanan->nama_lengkap},

            2 hari lagi menuju keberangkatan {$pesanan->nama_perjalanan} ({$pesanan->kode}) pada {$pesanan->tanggal_trip->translatedFormat('d F Y')}.

            {$detail}

            {$instruksiWaktu}

            Sampai jumpa!
            TEKS;
    }

    /** Ajakan mengisi ulasan setelah trip Selesai, menyertakan tautan token_rating. */
    public function ajakanUlasan(Pesanan $pesanan, string $tautanRating): string
    {
        return <<<TEKS
            Halo Kak {$pesanan->nama_lengkap},

            Terima kasih banyak sudah mempercayakan perjalanan {$pesanan->nama_perjalanan} ({$pesanan->kode}) bersama MISTBORNE.

            Kami senang bisa mendampingi ekspedisi Anda. Bagikan pengalaman Anda lewat tautan resmi berikut:
            {$tautanRating}

            Terima kasih!
            TEKS;
    }

    /**
     * Pesan pembuka tamu ke admin setelah mengirim form Rancang Trip Kustom: ramah dan lengkap,
     * dikelompokkan (rencana, preferensi, catatan, kontak) dan hanya memuat isian yang diisi tamu.
     */
    public function permintaanTripKustom(PesananKustom $kustom): string
    {
        $minat = [
            'alam' => 'Alam & pemandangan', 'budaya' => 'Budaya', 'kuliner' => 'Kuliner', 'fotografi' => 'Fotografi',
            'keluarga' => 'Ramah keluarga', 'petualangan' => 'Petualangan', 'sejarah' => 'Sejarah', 'belanja' => 'Belanja',
        ];
        $tempo = [
            'santai' => 'Santai, banyak waktu menikmati tempat',
            'normal' => 'Seimbang',
            'aktif' => 'Padat, ingin mengunjungi banyak tempat',
        ];

        $sapaan = match (true) {
            now()->hour < 11 => 'pagi',
            now()->hour < 15 => 'siang',
            now()->hour < 18 => 'sore',
            default => 'malam',
        };

        $jadwal = $kustom->tanggal_fleksibel || $kustom->tanggal_diinginkan === null
            ? 'Masih fleksibel, bisa menyesuaikan'
            : $kustom->tanggal_diinginkan->translatedFormat('l, d F Y');

        $rencana = [
            "• Destinasi: {$kustom->destinasi_diinginkan}",
            "• Rencana berangkat: {$jadwal}",
            "• Durasi: {$kustom->durasi_hari} hari",
            "• Jumlah peserta: {$kustom->jumlah_orang} orang",
            "• Area penjemputan: {$kustom->lokasi_penjemputan}",
            '• Penginapan: '.($kustom->status_penginapan === StatusPenginapan::Sudah
                ? ($kustom->nama_hotel ?: 'Sudah punya')
                : 'Belum punya, mohon dibantu rekomendasi'),
        ];

        $preferensi = [];
        if (! empty($kustom->minat_perjalanan)) {
            $preferensi[] = '• Yang paling kami sukai: '.collect($kustom->minat_perjalanan)->map(fn (string $kunci): string => $minat[$kunci] ?? $kunci)->implode(', ');
        }
        if (filled($kustom->tempo_perjalanan)) {
            $preferensi[] = '• Suasana perjalanan: '.($tempo[$kustom->tempo_perjalanan] ?? $kustom->tempo_perjalanan);
        }

        $kontak = [];
        if (filled($kustom->whatsapp)) {
            $kontak[] = "• Nomor WhatsApp: {$kustom->whatsapp}";
        }

        $blok = [
            "Halo Tim MISTBORNE, selamat {$sapaan} 🙏",
            "Perkenalkan, saya *{$kustom->nama}*. Saya ingin merancang trip kustom bersama MISTBORNE dan berharap bisa dibantu menyusun rencananya. Berikut gambaran singkat yang sudah saya isi di website:",
            "*Rencana perjalanan*\n".implode("\n", $rencana),
        ];
        if ($preferensi !== []) {
            $blok[] = "*Preferensi kami*\n".implode("\n", $preferensi);
        }
        if (filled($kustom->catatan)) {
            $blok[] = "*Gambaran trip yang saya inginkan*\n{$kustom->catatan}";
        }
        if ($kontak !== []) {
            $blok[] = "*Kontak tambahan*\n".implode("\n", $kontak);
        }
        $blok[] = 'Mohon bantuannya untuk menyampaikan rekomendasi rute, perkiraan biaya, dan ketersediaan armada pada waktu tersebut ya. Kalau ada informasi yang masih kurang, saya dengan senang hati melengkapinya.';
        $blok[] = 'Terima kasih banyak atas waktu dan bantuannya, mohon maaf bila merepotkan. Saya tunggu kabar baiknya 😊';

        return implode("\n\n", $blok);
    }

    /**
     * Pesan rekomendasi penginapan untuk tamu yang belum punya. Daftar hotel diambil dari
     * Pengaturan (diisi admin); bila kosong, pesan menjanjikan rekomendasi menyusul.
     */
    /** Tahap 1 alur kustom: pesan pembuka admin ke tamu untuk membahas permintaan trip kustomnya. */
    public function pembukaTripKustom(PesananKustom $kustom): string
    {
        return implode("\n", [
            'Halo '.$kustom->nama.', saya admin MISTBORNE.',
            'Saya menghubungi Anda terkait permintaan trip kustom '.$kustom->kodeTampilan().'.',
            'Destinasi: '.$kustom->destinasi_diinginkan,
            'Jadwal: '.$kustom->jadwalDiinginkanFormat(),
            'Peserta: '.$kustom->jumlah_orang.' orang.',
            'Kami ingin memastikan kebutuhan perjalanan Anda agar dapat menyiapkan rencana yang sesuai. Apakah saat ini waktu yang tepat untuk berdiskusi?',
        ]);
    }

    /** Tahap 3 alur kustom: penawaran rencana, harga, dan yang termasuk untuk disetujui tamu. */
    public function penawaranTripKustom(PesananKustom $kustom): string
    {
        $ringkasan = collect([
            'Destinasi: '.$kustom->destinasi_diinginkan,
            'Jadwal: '.$kustom->jadwalDiinginkanFormat(),
            $kustom->durasi_hari ? 'Durasi: '.$kustom->durasi_hari.' hari' : null,
            'Peserta: '.$kustom->jumlah_orang.' orang',
            $kustom->lokasi_penjemputan ? 'Area jemput: '.$kustom->lokasi_penjemputan : null,
            'Penginapan: tidak termasuk dalam harga (dipesan dan dibayar langsung oleh tamu)'.($kustom->status_penginapan === StatusPenginapan::Belum ? '; kami kirimkan rekomendasi penginapan terpisah.' : '.'),
        ])->filter()->implode("\n");

        $penutup = $kustom->tanggal_fleksibel || $kustom->tanggal_diinginkan === null
            ? 'Bila Kakak setuju, balas "SETUJU" sekaligus kabari tanggal pasti keberangkatan agar jadwalnya bisa kami kunci. Bila ada yang ingin diubah (rute, durasi, atau jumlah peserta), sampaikan saja, kami sesuaikan penawarannya.'
            : 'Bila Kakak setuju, cukup balas "SETUJU". Kami akan membuat pesanan resmi dan mengirim rincian pembayarannya. Bila ada yang ingin diubah (rute, tanggal, atau jumlah peserta), sampaikan saja, kami sesuaikan penawarannya.';

        return collect([
            'Halo Kak '.$kustom->nama.', terima kasih sudah menunggu. Berikut penawaran trip kustom MISTBORNE untuk Kakak.',
            "*Ringkasan*\n".$ringkasan,
            filled($kustom->rencana_perjalanan) ? "*Rencana perjalanan*\n".trim($kustom->rencana_perjalanan) : null,
            "*Harga*\n".FormatRupiah::dari($kustom->harga_final).' per orang × '.$kustom->jumlah_orang.' orang = '.FormatRupiah::dari($kustom->hargaTotal()).' (total grup)',
            filled($kustom->rincian_penawaran) ? "*Termasuk & tidak termasuk*\n".trim($kustom->rincian_penawaran) : null,
            $kustom->batas_penawaran ? 'Penawaran ini berlaku sampai '.$kustom->batas_penawaran->translatedFormat('d F Y').'.' : null,
            $penutup,
        ])->filter()->implode("\n\n");
    }

    public function rekomendasiPenginapan(Pesanan|PermintaanPesanan|PesananKustom $sumber): string
    {
        $namaTamu = $sumber instanceof PesananKustom ? $sumber->nama : $sumber->nama_lengkap;
        $nama = filled($namaTamu) ? ' '.$namaTamu : '';
        $perjalanan = $sumber instanceof PesananKustom ? null : $sumber->perjalanan;
        $daftarHotel = $perjalanan
            ? $perjalanan->hotel()->aktif()->orderBy('nama')->limit(5)->get()
            : collect();
        $daftarJourney = trim((string) $perjalanan?->rekomendasi_penginapan);
        $daftarUmum = trim((string) Pengaturan::current()->rekomendasi_penginapan);

        if ($daftarHotel->isNotEmpty()) {
            $daftar = $daftarHotel->map(fn (Hotel $hotel): string => $this->barisHotel($hotel))->implode("\n\n");
            $tujuan = filled($perjalanan?->tempat) ? ' di sekitar '.$perjalanan->tempat : ' di sekitar tujuan Kakak';
            $isiDaftar = "Berikut beberapa penginapan yang kami rekomendasikan{$tujuan}:\n\n{$daftar}\n\n(Kelas bintang mengacu pada situs pemesanan; mohon cek harga dan ketersediaannya langsung.)\n\nKalau ada yang cocok, kabari kami hotel pilihan Kakak. Kami bisa membantu memesankannya, dan pembayaran tetap dilakukan Kakak langsung ke hotel.";
        } elseif ($daftarJourney !== '' || $daftarUmum !== '') {
            $daftar = $daftarJourney !== '' ? $daftarJourney : $daftarUmum;
            $tujuan = $daftarJourney !== '' && filled($perjalanan?->tempat) ? ' di sekitar '.$perjalanan->tempat : ' di sekitar tujuan Kakak';
            $isiDaftar = 'Berikut beberapa penginapan'.($daftarJourney !== '' ? ' bintang 4' : '')." yang kami rekomendasikan{$tujuan}:\n\n{$daftar}\n\n(Kelas bintang mengacu pada situs pemesanan; mohon cek harga dan ketersediaannya langsung.)";
        } else {
            $isiDaftar = 'Rekomendasi penginapan di sekitar tujuan Kakak akan kami kirimkan segera setelah daftarnya kami sesuaikan dengan tanggal perjalanan.';
        }

        $jumlahOrang = $sumber instanceof PesananKustom ? (int) $sumber->jumlah_orang : (int) $sumber->jumlah_wisatawan;
        $perkiraanKamar = $this->perkiraanKamar($jumlahOrang);
        $penutupPenginapan = $daftarHotel->isNotEmpty()
            ? 'Biaya penginapan tidak termasuk dalam harga layanan kami. Bila Kakak memesan sendiri, mohon kabari nama penginapannya ke kami agar sopir bisa menjemput dari sana pada hari perjalanan.'
            : 'Penginapan dipesan dan dibayar langsung oleh Kakak ke pihak penginapan (tidak termasuk dalam harga layanan kami). Setelah memesan, mohon kabari nama penginapannya ke kami agar sopir bisa menjemput dari sana pada hari perjalanan.';

        $tautanFormulir = $sumber instanceof PermintaanPesanan && $sumber->dapatDiubahTamu()
            ? "\n\nBila ingin mengubah data lewat formulir, gunakan tautan berikut, berlaku sampai {$sumber->token_berlaku_sampai->translatedFormat('d F Y, H:i')} WIB:\n".route('form-pesanan.tampil', $sumber->token)
            : '';
        $kodePesanan = $sumber instanceof Pesanan ? $sumber->kode : null;
        $tautanKonfirmasi = $this->tautanKonfirmasiWa($this->pesanPilihPenginapanSendiri($namaTamu, $kodePesanan));

        return <<<TEKS
            Halo Kak{$nama},

            Terima kasih sudah memesan bersama MISTBORNE. Karena Kakak belum memiliki penginapan, izinkan kami membantu dengan rekomendasi.

            {$isiDaftar}

            {$perkiraanKamar}

            {$penutupPenginapan}

            Kalau Kakak kurang cocok dengan rekomendasi kami atau ingin memesan kamar atas nama sendiri, tidak masalah. Cukup konfirmasi nama dan alamat hotelnya lewat WhatsApp berikut agar rincian trip dari jemput sampai selesai tercatat rapi dan tidak ada yang terlewat:
            {$tautanKonfirmasi}{$tautanFormulir}

            Kalau butuh pertimbangan lain, misalnya anggaran atau lokasi, kabari saja ya, Kak. Terima kasih!
            TEKS;
    }

    /** Satu baris rekomendasi hotel: nama, bintang, area, tarif, fasilitas, dan tautan peta. */
    private function barisHotel(Hotel $hotel): string
    {
        $baris = ["• {$hotel->nama} {$hotel->labelBintang()} — {$hotel->area}"];

        if ($hotel->rentangTarif()) {
            $baris[] = "  Tarif: {$hotel->rentangTarif()} per kamar per malam";
        }
        if ($hotel->teksFasilitas() !== '') {
            $baris[] = "  Fasilitas: {$hotel->teksFasilitas()}";
        }
        if (filled($hotel->tautan_peta)) {
            $baris[] = "  Peta: {$hotel->tautan_peta}";
        }

        return implode("\n", $baris);
    }

    /** Permintaan reservasi ke hotel: dikirim admin ke WhatsApp hotel sebagai draft. */
    public function permintaanReservasiHotel(Pesanan $pesanan): string
    {
        $hotel = $pesanan->hotel;
        $sapaan = match (true) {
            now()->hour < 11 => 'pagi',
            now()->hour < 15 => 'siang',
            now()->hour < 18 => 'sore',
            default => 'malam',
        };
        $sapa = filled($hotel?->nama_pic) ? "Selamat {$sapaan}, Bapak/Ibu {$hotel->nama_pic}" : "Selamat {$sapaan}, Tim Reservasi {$hotel?->nama}";
        $malam = $pesanan->hotel_check_in && $pesanan->hotel_check_out ? (int) $pesanan->hotel_check_in->diffInDays($pesanan->hotel_check_out) : 0;
        $kamar = (int) $pesanan->jumlah_kamar;
        $tipe = filled($pesanan->tipe_kamar) ? " ({$pesanan->tipe_kamar})" : '';
        $catatan = filled($pesanan->catatan_reservasi_hotel) ? "\n• Catatan: {$pesanan->catatan_reservasi_hotel}" : '';
        $pembayaran = $pesanan->status_penginapan === StatusPenginapan::Diurus
            ? 'Pembayaran akan kami selesaikan setelah reservasi terkonfirmasi; mohon informasikan cara dan batas waktunya.'
            : 'Pembayaran kamar dilakukan tamu langsung di hotel saat check-in.';
        $kontak = (string) Pengaturan::current()->nomor_whatsapp_admin;

        return <<<TEKS
            {$sapa} 🙏

            Kami dari MISTBORNE Private Highland Travel. Kami ingin memesankan kamar untuk tamu kami dengan rincian berikut:

            • Nama tamu: {$pesanan->nama_lengkap}
            • Jumlah tamu: {$pesanan->jumlah_wisatawan} orang
            • Check-in: {$pesanan->hotel_check_in?->translatedFormat('l, d F Y')} (mulai {$hotel?->jamCheckIn()})
            • Check-out: {$pesanan->hotel_check_out?->translatedFormat('l, d F Y')} (sebelum {$hotel?->jamCheckOut()})
            • Lama menginap: {$malam} malam
            • Kamar: {$kamar} kamar{$tipe}{$catatan}

            Mohon dibantu konfirmasi ketersediaan, tarif per kamar per malam (termasuk sarapan atau tidak), serta kebijakan pembatalan. Setelah terkonfirmasi, mohon sertakan kode booking atau nama petugas yang mengonfirmasi.

            {$pembayaran}

            Kontak kami untuk koordinasi: +{$kontak}. Terima kasih banyak atas bantuannya.
            TEKS;
    }

    /** Aturan yang disampaikan ke tamu: hotel yang sudah terkonfirmasi tidak bisa diganti, kecuali dengan biaya. */
    public const CATATAN_GANTI_HOTEL = 'Catatan: hotel yang sudah terkonfirmasi tidak dapat diganti. Bila Kakak tetap ingin mengganti hotel, berlaku biaya (charge) penggantian sesuai ketentuan hotel dan MISTBORNE; tim kami akan menyampaikan nominalnya terlebih dahulu.';

    /**
     * Rincian reservasi hotel lengkap dengan jam check-in/check-out, tipe kamar, kode booking, alamat, dan peta.
     * Dipakai pesan konfirmasi dan info penginapan supaya tamu tidak perlu menanyakan jam lagi.
     */
    private function rincianReservasiHotel(Pesanan $pesanan, bool $denganKode = true): string
    {
        $hotel = $pesanan->hotel;
        $malam = $pesanan->hotel_check_in && $pesanan->hotel_check_out ? (int) $pesanan->hotel_check_in->diffInDays($pesanan->hotel_check_out) : 0;
        $tipe = filled($pesanan->tipe_kamar) ? " tipe {$pesanan->tipe_kamar}" : '';
        $baris = [
            "• Hotel: {$hotel?->nama} {$hotel?->labelBintang()}",
            "• Check-in: {$pesanan->hotel_check_in?->translatedFormat('l, d F Y')}, mulai pukul {$hotel?->jamCheckIn()} WIB",
            "• Check-out: {$pesanan->hotel_check_out?->translatedFormat('l, d F Y')}, paling lambat pukul {$hotel?->jamCheckOut()} WIB",
            "• Kamar: {$pesanan->jumlah_kamar} kamar{$tipe} untuk {$malam} malam",
        ];
        if (filled($hotel?->alamat)) {
            $baris[] = "• Alamat: {$hotel->alamat}";
        }
        $baris[] = '• Peta: '.$hotel?->petaUrl();
        if ($denganKode && filled($pesanan->kode_booking_hotel)) {
            $baris[] = "• Kode booking hotel: {$pesanan->kode_booking_hotel}";
        }

        return implode("\n", $baris);
    }

    /** Konfirmasi hotel ke tamu setelah reservasi terkonfirmasi. */
    public function konfirmasiHotelKeTamu(Pesanan $pesanan): string
    {
        $hotel = $pesanan->hotel;
        $rincian = $this->rincianReservasiHotel($pesanan);
        $tarif = $hotel?->rentangTarif() ? "Perkiraan tarif hotel: {$hotel->rentangTarif()} per kamar per malam. " : '';
        $pembayaran = $pesanan->status_penginapan === StatusPenginapan::Diurus
            ? 'Biaya penginapan sudah termasuk dalam harga all-inclusive Kakak, jadi tidak ada pembayaran tambahan ke hotel (kecuali pengeluaran pribadi).'
            : $tarif.'Pembayaran kamar dilakukan Kakak langsung ke hotel saat check-in.';
        $kebijakan = filled($hotel?->kebijakan_pembatalan) ? "\n\nKebijakan pembatalan hotel: {$hotel->kebijakan_pembatalan}" : '';
        $gantiHotel = self::CATATAN_GANTI_HOTEL;

        return <<<TEKS
            Halo Kak {$pesanan->nama_lengkap},

            Kabar baik, kamar Kakak sudah disiapkan dan terkonfirmasi oleh pihak hotel 🎉

            {$rincian}

            {$pembayaran}{$kebijakan}

            Mohon membawa kartu identitas saat check-in. Sopir kami akan menjemput sesuai titik jemput yang sudah disepakati. Kalau ada perubahan rencana, kabari kami secepatnya ya, Kak.

            {$gantiHotel}

            Terima kasih!
            TEKS;
    }

    /** Kabar ke admin hotel bahwa MISTBORNE sudah mentransfer pembayaran; berkas bukti dilampirkan admin secara manual. */
    public function buktiTransferKeHotel(Pesanan $pesanan): string
    {
        $hotel = $pesanan->hotel;
        $kode = filled($pesanan->kode_booking_hotel) ? " (kode booking: {$pesanan->kode_booking_hotel})" : '';
        $nominal = $pesanan->bayar_hotel_nominal !== null ? FormatRupiah::dari($pesanan->bayar_hotel_nominal) : '(isi nominal transfer)';
        $tanggal = $pesanan->bayar_hotel_tanggal?->translatedFormat('d F Y') ?? '(isi tanggal transfer)';
        $tipe = filled($pesanan->tipe_kamar) ? " ({$pesanan->tipe_kamar})" : '';

        return <<<TEKS
            Selamat {$this->sapaanWaktu()}, Tim Reservasi {$hotel?->nama} 🙏

            Kami dari MISTBORNE Private Highland Travel. Kami sudah melakukan transfer untuk reservasi atas nama {$pesanan->nama_lengkap}{$kode}:

            • Nominal: {$nominal}
            • Tanggal transfer: {$tanggal}
            • Check-in: {$pesanan->hotel_check_in?->translatedFormat('l, d F Y')}
            • Check-out: {$pesanan->hotel_check_out?->translatedFormat('l, d F Y')}
            • Kamar: {$pesanan->jumlah_kamar} kamar{$tipe}

            Bukti transfer kami lampirkan pada pesan ini. Mohon dicek dan dikabari bila pembayaran sudah diterima. Terima kasih banyak.
            TEKS;
    }

    /** Konfirmasi ulang ke hotel menjelang hari menginap supaya kamar untuk tamu tidak terlewat. */
    public function konfirmasiUlangKeHotel(Pesanan $pesanan): string
    {
        $hotel = $pesanan->hotel;
        $kode = filled($pesanan->kode_booking_hotel) ? " (kode booking: {$pesanan->kode_booking_hotel})" : '';
        $tipe = filled($pesanan->tipe_kamar) ? " ({$pesanan->tipe_kamar})" : '';
        $pembayaran = $pesanan->bayar_hotel_tanggal
            ? "\nPembayaran sudah kami transfer pada {$pesanan->bayar_hotel_tanggal->translatedFormat('d F Y')}."
            : '';

        return <<<TEKS
            Selamat {$this->sapaanWaktu()}, Tim Reservasi {$hotel?->nama} 🙏

            Kami dari MISTBORNE Private Highland Travel. Kami ingin memastikan kembali reservasi atas nama {$pesanan->nama_lengkap}{$kode}:

            • Jumlah tamu: {$pesanan->jumlah_wisatawan} orang
            • Check-in: {$pesanan->hotel_check_in?->translatedFormat('l, d F Y')} (mulai {$hotel?->jamCheckIn()})
            • Check-out: {$pesanan->hotel_check_out?->translatedFormat('l, d F Y')} (sebelum {$hotel?->jamCheckOut()})
            • Kamar: {$pesanan->jumlah_kamar} kamar{$tipe}{$pembayaran}

            Mohon dikonfirmasi bahwa kamar sudah siap untuk tamu kami. Jika ada perubahan, kabari kami secepatnya ya. Terima kasih banyak.
            TEKS;
    }

    /** Pembatalan atau perubahan reservasi ke hotel. */
    public function pembatalanReservasiHotel(Pesanan $pesanan): string
    {
        $hotel = $pesanan->hotel;
        $kode = filled($pesanan->kode_booking_hotel) ? " (kode booking: {$pesanan->kode_booking_hotel})" : '';

        return <<<TEKS
            Selamat {$this->sapaanWaktu()}, Tim Reservasi {$hotel?->nama} 🙏

            Kami dari MISTBORNE Private Highland Travel. Mohon maaf, kami perlu membatalkan reservasi atas nama {$pesanan->nama_lengkap}{$kode} untuk check-in {$pesanan->hotel_check_in?->translatedFormat('d F Y')}.

            Mohon konfirmasi pembatalan dan informasikan bila ada biaya yang berlaku sesuai kebijakan hotel. Terima kasih atas pengertian dan bantuannya.
            TEKS;
    }

    private function sapaanWaktu(): string
    {
        return match (true) {
            now()->hour < 11 => 'pagi',
            now()->hour < 15 => 'siang',
            now()->hour < 18 => 'sore',
            default => 'malam',
        };
    }

    /**
     * Info penginapan untuk tamu all-inclusive. Isinya mengikuti kemajuan reservasi: hotel sedang disiapkan, sudah diajukan
     * ke hotel (menunggu jawaban), atau sudah terkonfirmasi lengkap dengan jam check-in/check-out.
     */
    public function infoPenginapanAllIn(Pesanan $pesanan): string
    {
        $kamar = (int) ($pesanan->jumlah_kamar ?: ceil(max(1, $pesanan->jumlah_wisatawan) / max(1, (int) config('mistborne.biaya_perjalanan.tamu_per_kamar'))));
        $reservasiAktif = $pesanan->hotel_id !== null && $pesanan->status_reservasi_hotel !== null
            && $pesanan->status_reservasi_hotel !== StatusReservasiHotel::Dibatalkan;
        $terkonfirmasi = $reservasiAktif && $pesanan->status_reservasi_hotel === StatusReservasiHotel::Terkonfirmasi;

        if ($terkonfirmasi) {
            $isi = "Kamar Kakak sudah disiapkan dan terkonfirmasi oleh pihak hotel 🎉\n\n".$this->rincianReservasiHotel($pesanan)."\n\nMohon membawa kartu identitas saat check-in. Sopir kami akan menjemput dari titik jemput yang sudah disepakati.\n\n".self::CATATAN_GANTI_HOTEL;
        } elseif ($reservasiAktif) {
            $isi = "Kami sudah mengajukan kamar untuk Kakak ke hotel dan sedang menunggu jawaban pihak hotel:\n\n".$this->rincianReservasiHotel($pesanan, denganKode: false)."\n\nBegitu hotel mengonfirmasi, kami langsung kabari Kakak lagi di sini.";
        } elseif (filled($pesanan->nama_hotel)) {
            $isi = "Penginapan Kakak selama perjalanan sudah kami pesankan:\n\n• {$pesanan->nama_hotel}\n• Jumlah kamar: {$kamar} (4 orang per kamar)\n\nSopir kami akan menjemput dari titik jemput yang sudah disepakati. Jam check-in dan check-out kami kabarkan segera setelah hotel mengonfirmasi.";
        } else {
            $isi = 'Hotel untuk Kakak sedang kami siapkan (sekitar '.$kamar.' kamar, 4 orang per kamar). Begitu sudah terkonfirmasi, nama hotel beserta jam check-in dan check-out langsung kami kabarkan di sini.';
        }

        return <<<TEKS
            Halo Kak {$pesanan->nama_lengkap},

            Terima kasih sudah memilih paket all-inclusive MISTBORNE untuk {$pesanan->nama_perjalanan} ({$pesanan->kode}). Penginapan dan tiket masuk objek wisata sudah termasuk dalam harga paket Kakak, jadi tidak ada biaya mendadak di lokasi.

            {$isi}

            Kalau ada preferensi khusus, misalnya kamar bersebelahan atau kebutuhan lain, kabari kami ya, Kak. Terima kasih!
            TEKS;
    }

    /** Perkiraan jumlah kamar untuk rombongan (4 orang per kamar) beserta saran kamar keluarga. */
    private function perkiraanKamar(int $jumlahOrang): string
    {
        if ($jumlahOrang < 1) {
            return 'Mohon sesuaikan jumlah kamar dengan jumlah peserta rombongan Kakak.';
        }

        $kamar = (int) ceil($jumlahOrang / max(1, (int) config('mistborne.biaya_perjalanan.tamu_per_kamar')));

        if ($jumlahOrang === 1) {
            return 'Untuk 1 orang, Kakak cukup memesan 1 kamar.';
        }

        $saran = $jumlahOrang >= 3
            ? ' Beberapa penginapan juga punya kamar keluarga untuk 3–4 orang, jadi bisa lebih hemat; tarif tiap kamar berbeda-beda, mohon dicek langsung.'
            : ' Tarif tiap kamar berbeda-beda, mohon dicek langsung.';

        return "Untuk {$jumlahOrang} orang, perkiraan kebutuhan sekitar {$kamar} kamar (4 orang per kamar).".$saran;
    }

    /**
     * Pesan tamu ke admin setelah memperbarui formulir: ringkasan isian terbaru agar admin bisa memeriksanya
     * tanpa membuka panel, sehingga tidak ada selisih data antara formulir dan percakapan.
     */
    public function pembaruanDariTamu(PermintaanPesanan $permintaan): string
    {
        $jam = $permintaan->waktu_pickup ? substr((string) $permintaan->waktu_pickup, 0, 5).' WIB' : 'Belum ditentukan';

        return trim(<<<TEKS
            Halo MISTBORNE, saya {$permintaan->nama_lengkap}. Saya sudah memperbarui data perjalanan lewat formulir:

            Nomor WhatsApp saya: {$permintaan->whatsapp}
            Journey: {$permintaan->perjalanan?->nama}
            Tanggal: {$permintaan->tanggal_trip?->translatedFormat('d F Y')}
            Jumlah peserta: {$permintaan->jumlah_wisatawan} orang
            {$this->barisPenginapan($permintaan->status_penginapan, $permintaan->nama_hotel, $permintaan->alamat_hotel, 'Penginapan')}
            Lokasi jemput: {$permintaan->lokasi_pickup}
            Jam jemput: {$jam}

            Mohon dicek dan dikonfirmasi ya. Terima kasih.
            TEKS);
    }

    /** Tautan wa.me ke nomor admin dengan draf pesan dari tamu. */
    private function tautanKonfirmasiWa(string $pesan): string
    {
        return 'https://wa.me/'.preg_replace('/\D/', '', (string) Pengaturan::current()->nomor_whatsapp_admin).'?text='.rawurlencode($pesan);
    }

    private function pesanKonfirmasiTamu(?string $namaTamu, ?string $journey): string
    {
        $nama = filled($namaTamu) ? $namaTamu : '(nama Kakak)';
        $tujuan = filled($journey) ? " untuk {$journey}" : '';

        return "Halo MISTBORNE, saya {$nama}. Saya sudah memeriksa dan memperbarui data perjalanan{$tujuan}. Mohon dikonfirmasi ya.";
    }

    private function pesanPilihPenginapanSendiri(?string $namaTamu, ?string $kodePesanan): string
    {
        $nama = filled($namaTamu) ? $namaTamu : '(nama Kakak)';
        $kode = $kodePesanan ? " (pesanan {$kodePesanan})" : '';

        return "Halo MISTBORNE, saya {$nama}{$kode}. Saya ingin memesan penginapan sendiri.\n\nNama hotel: \nAlamat hotel: \nAtas nama: \n\nMohon dicatat sebagai titik jemput dan antar saya. Terima kasih.";
    }

    /** Baris "Penginapan" (dan alamat hotel bila ada) untuk pesan ke tamu. */
    private function barisPenginapan(?StatusPenginapan $status, ?string $nama, ?string $alamat, string $label = 'Penginapan'): string
    {
        $baris = "{$label}: {$this->teksPenginapan($status, $nama)}";

        return filled($alamat) ? $baris."\nAlamat Hotel: {$alamat}" : $baris;
    }

    /** Rincian trip untuk invoice: durasi, penginapan, dan titik jemput. */
    private function rincianTrip(Pesanan $pesanan): string
    {
        $durasi = $pesanan->durasi_hari ?: $pesanan->perjalanan?->durasi_hari;
        $baris = array_filter([
            $durasi ? 'Durasi: '.$durasi.' hari '.max(0, $durasi - 1).' malam (selesai '.($pesanan->tanggal_selesai ?? $pesanan->tanggal_trip)->translatedFormat('d F Y').')' : null,
            $this->barisPenginapan($pesanan->status_penginapan, $pesanan->nama_hotel, $pesanan->alamat_hotel),
            'Lokasi Jemput: '.$pesanan->lokasi_pickup,
        ]);

        return implode("\n", $baris);
    }

    /** Teks penginapan untuk pesan ke tamu: nama hotel, atau keterangan bahwa rekomendasi menyusul. */
    private function teksPenginapan(?StatusPenginapan $status, ?string $nama): string
    {
        if (filled($nama)) {
            return $nama;
        }

        return match ($status) {
            StatusPenginapan::Belum => 'Belum ada (kami bantu rekomendasi)',
            StatusPenginapan::Dipesankan => 'Kami pesankan (hotel kami informasikan menyusul)',
            StatusPenginapan::Diurus => 'All-inclusive, diurus MISTBORNE (hotel kami informasikan menyusul)',
            default => 'Belum diisi',
        };
    }

    public function pembatalan(Pesanan $pesanan): string
    {
        return <<<TEKS
            Halo Kak {$pesanan->nama_lengkap},

            Pesanan {$pesanan->kode} ({$pesanan->nama_perjalanan}) telah dibatalkan.

            Alasan: {$pesanan->alasan_batal}
            Nominal Refund: {$this->rupiah($pesanan->nominal_refund)}

            Mohon konfirmasi rekening tujuan refund Anda. Terima kasih atas pengertiannya.
            TEKS;
    }

    private function rupiah(?int $nominal): string
    {
        return FormatRupiah::dari($nominal) ?? 'Rp 0';
    }
}
