<?php

namespace App\Http\Controllers\Admin;

use App\Aksi\Pesanan\BuatPesananManual;
use App\Bantuan\NomorWhatsApp;
use App\Enums\BookingStatus;
use App\Enums\SumberPesanan;
use App\Http\Controllers\Controller;
use App\Kontrak\PengirimWhatsApp;
use App\Layanan\KapasitasTripLayanan;
use App\Models\PermintaanPesanan;
use App\Models\PertanyaanTamu;
use App\Models\Pesanan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PermintaanPesananController extends Controller
{
    public function kirimForm(Request $request, PengirimWhatsApp $pengirim): RedirectResponse
    {
        $request->merge(['whatsapp' => NomorWhatsApp::lokal($request->input('whatsapp'))]);

        $data = $request->validate([
            'whatsapp' => ['required', 'string', 'regex:/^08[0-9]{7,12}$/'],
            'pertanyaan_id' => ['nullable', 'integer', 'exists:pertanyaan_tamu,id'],
        ], [
            'whatsapp.required' => 'Masukkan nomor WhatsApp tamu.',
            'whatsapp.regex' => 'Masukkan nomor WhatsApp dengan angka saja, diawali 08 atau +62, contoh 081234567890.',
        ]);

        $permintaan = PermintaanPesanan::query()->create([
            'token' => Str::random(64),
            'whatsapp' => $data['whatsapp'],
            'status' => 'menunggu_tamu',
            'token_berlaku_sampai' => PermintaanPesanan::batasBerlakuBaru(),
        ]);

        // Mengirim form belum berarti tamu booking: pertanyaan hanya ditautkan dan ditandai "dibalas".
        // Status "jadi booking" baru diberikan saat tamu benar-benar mengisi formulir.
        if (filled($data['pertanyaan_id'] ?? null)) {
            PertanyaanTamu::query()
                ->whereKey($data['pertanyaan_id'])
                ->where('status', '!=', 'jadi_booking')
                ->update(['permintaan_pesanan_id' => $permintaan->id]);
            PertanyaanTamu::query()
                ->whereKey($data['pertanyaan_id'])
                ->whereIn('status', ['baru', 'tidak_lanjut'])
                ->update(['status' => 'dibalas']);
        }

        $tautanForm = route('form-pesanan.tampil', $permintaan->token);
        $pesan = "Halo Kak, terima kasih sudah menghubungi MISTBORNE 😊\n\n".
            "Agar kami bisa menyiapkan rencana perjalanan yang sesuai, mohon lengkapi formulir melalui tautan berikut:\n".
            "{$tautanForm}\n\n".
            "Di dalam formulir, Kakak dapat memilih journey, tanggal keberangkatan melalui kalender, jumlah peserta, serta mengisi hotel dan lokasi penjemputan. Pilih tanggal minimal 7 hari dari hari ini.\n\n".
            'Tautan ini berlaku sampai '.$permintaan->token_berlaku_sampai->translatedFormat('d F Y, H:i')." WIB. Bila ada perubahan setelahnya, kabari kami dan kami kirim tautan baru.\n\n".
            "Setelah formulir dikirim, tim kami akan mengecek ketersediaan tanggal dan slot, lalu menghubungi Kakak untuk memastikan detail perjalanan. Pengiriman formulir ini belum mengunci slot dan belum menjadi konfirmasi pesanan.\n\n".
            'Jika ada kebutuhan khusus, silakan tuliskan di bagian catatan pada formulir. Terima kasih, Kak!';

        return redirect()->away($pengirim->buatTautan($permintaan->whatsapp, $pesan));
    }

    /** Tautan lama mati dan tamu menerima tautan baru yang berlaku 1 hari (untuk perubahan data dari tamu). */
    public function kirimUlangForm(PermintaanPesanan $permintaan, PengirimWhatsApp $pengirim): RedirectResponse
    {
        $terkunci = DB::transaction(function () use ($permintaan): PermintaanPesanan {
            $terkunci = PermintaanPesanan::query()->whereKey($permintaan->id)->lockForUpdate()->firstOrFail();
            $this->pastikanBelumDitutup($terkunci);

            $terkunci->update([
                'token' => Str::random(64),
                'token_berlaku_sampai' => PermintaanPesanan::batasBerlakuBaru(),
            ]);

            return $terkunci;
        });

        $pesan = "Halo Kak, berikut tautan formulir terbaru untuk memperbarui data perjalanan Kakak:\n".
            route('form-pesanan.tampil', $terkunci->token)."\n\n".
            'Tautan berlaku sampai '.$terkunci->token_berlaku_sampai->translatedFormat('d F Y, H:i').' WIB. '.
            'Tautan sebelumnya sudah tidak berlaku. Bila perlu perubahan lagi, kabari kami ya, Kak.';

        return redirect()->away($pengirim->buatTautan($terkunci->whatsapp, $pesan));
    }

    public function index(): View
    {
        $permintaan = PermintaanPesanan::query()
            ->with(['perjalanan', 'pesanan'])
            ->latest()
            ->get();

        // Ketersediaan tanggal tiap permintaan dengan aturan yang sama seperti semua formulir (Tanggal Tersedia, kuota journey, guide bebas).
        $kapasitas = app(KapasitasTripLayanan::class);
        $ketersediaan = $permintaan->mapWithKeys(function (PermintaanPesanan $item) use ($kapasitas): array {
            if (! $item->perjalanan || ! $item->tanggal_trip) {
                return [$item->id => null];
            }

            $slot = $kapasitas->cek($item->perjalanan, $item->tanggal_trip->toDateString());

            return [$item->id => [
                'tersedia' => $slot['tersedia'],
                'sisa' => $slot['sisa'],
                'status' => $slot['status'],
                'alasan' => $slot['alasan'],
            ]];
        });

        return view('admin.permintaan-pesanan.indeks', [
            'permintaan' => $permintaan,
            'ketersediaan' => $ketersediaan,
        ]);
    }

    public function tindakLanjut(Request $request, PermintaanPesanan $permintaan): RedirectResponse
    {
        $data = $request->validate([
            'catatan_admin' => ['nullable', 'string', 'max:1000'],
        ], [
            'catatan_admin.max' => 'Catatan admin maksimal 1.000 karakter.',
        ]);

        DB::transaction(function () use ($permintaan, $data): void {
            $terkunci = PermintaanPesanan::query()->whereKey($permintaan->id)->lockForUpdate()->firstOrFail();
            $this->pastikanBelumDitutup($terkunci);

            if ($terkunci->status === 'menunggu_tamu') {
                throw ValidationException::withMessages(['status' => 'Tamu belum mengisi formulir ini.']);
            }

            $terkunci->update([
                'status' => 'ditindaklanjuti',
                'catatan_admin' => $data['catatan_admin'] ?? $terkunci->catatan_admin,
            ]);
        });

        return back()->with('sukses', 'Permintaan ditandai sedang ditindaklanjuti.');
    }

    public function buatPesanan(PermintaanPesanan $permintaan, BuatPesananManual $buatPesanan): RedirectResponse
    {
        $pesanan = DB::transaction(function () use ($permintaan, $buatPesanan): Pesanan {
            $terkunci = PermintaanPesanan::query()->whereKey($permintaan->id)->lockForUpdate()->firstOrFail();
            $this->pastikanBelumDitutup($terkunci);

            if (! in_array($terkunci->status, ['menunggu_tinjauan', 'ditindaklanjuti'], true)) {
                throw ValidationException::withMessages(['status' => 'Tamu harus mengisi formulir sebelum pesanan dibuat.']);
            }

            $perjalanan = $terkunci->perjalanan;
            if (! $perjalanan) {
                throw ValidationException::withMessages(['perjalanan_id' => 'Journey permintaan ini sudah tidak tersedia.']);
            }

            $pesanan = $buatPesanan->handle($perjalanan, [
                'tanggal_trip' => $terkunci->tanggal_trip?->toDateString(),
                'nama_lengkap' => $terkunci->nama_lengkap,
                'whatsapp' => '62'.substr($terkunci->whatsapp, 1),
                'jumlah_wisatawan' => $terkunci->jumlah_wisatawan,
                'nama_hotel' => $terkunci->nama_hotel,
                'alamat_hotel' => $terkunci->alamat_hotel,
                'kendaraan_pilihan' => $terkunci->kendaraan_pilihan,
                'status_penginapan' => $terkunci->status_penginapan?->value,
                'lokasi_pickup' => $terkunci->lokasi_pickup,
                'waktu_pickup' => $terkunci->waktu_pickup,
                'paket_opsional' => $terkunci->paket_opsional,
                'permintaan_khusus' => $terkunci->permintaan_khusus,
                'nominal_dibayar' => null,
                'metode_pembayaran' => null,
                'status' => BookingStatus::Dihubungi,
                ...($terkunci->dariBookingWebsite() ? [
                    'sumber' => SumberPesanan::Form,
                    'user_web_id' => $terkunci->user_web_id,
                    'token_booking' => $terkunci->token_booking,
                ] : []),
            ]);

            $terkunci->update(['status' => 'disetujui', 'pesanan_id' => $pesanan->id]);

            return $pesanan;
        });

        return redirect()->route('admin.pesanan.detail', $pesanan)
            ->with('sukses', 'Permintaan disepakati dan sudah dibuat sebagai pesanan.')
            ->with('pesanan_baru_dari_permintaan', true);
    }

    public function tidakJadi(Request $request, PermintaanPesanan $permintaan): RedirectResponse
    {
        $data = $request->validate([
            'alasan_tidak_jadi' => ['nullable', 'string', 'max:1000'],
        ], [
            'alasan_tidak_jadi.max' => 'Alasan maksimal 1.000 karakter.',
        ]);

        DB::transaction(function () use ($permintaan, $data): void {
            $terkunci = PermintaanPesanan::query()->whereKey($permintaan->id)->lockForUpdate()->firstOrFail();
            $this->pastikanBelumDitutup($terkunci);

            if ($terkunci->status === 'disetujui') {
                throw ValidationException::withMessages(['status' => 'Permintaan ini sudah menjadi pesanan.']);
            }

            $terkunci->update(['status' => 'tidak_jadi', 'alasan_tidak_jadi' => $data['alasan_tidak_jadi'] ?? null]);

            // Pertanyaan asal tidak boleh tetap "jadi booking" bila permintaannya ditutup tanpa pesanan.
            PertanyaanTamu::query()
                ->where('permintaan_pesanan_id', $terkunci->id)
                ->update(['status' => 'tidak_lanjut']);
        });

        return back()->with('sukses', 'Permintaan ditandai tidak jadi.');
    }

    private function pastikanBelumDitutup(PermintaanPesanan $permintaan): void
    {
        if (in_array($permintaan->status, ['disetujui', 'tidak_jadi'], true)) {
            throw ValidationException::withMessages(['status' => 'Permintaan ini sudah ditutup.']);
        }
    }
}
