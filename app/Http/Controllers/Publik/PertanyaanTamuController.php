<?php

namespace App\Http\Controllers\Publik;

use App\Bantuan\NomorWhatsApp;
use App\Http\Controllers\Controller;
use App\Models\PertanyaanTamu;
use App\Models\User;
use App\Notifications\PertanyaanTamuMasukNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Mencatat pertanyaan calon tamu (form kontak, tombol WhatsApp) tepat sebelum browser
 * membuka WhatsApp, sehingga admin punya catatan walau tamu belum tentu memesan.
 */
class PertanyaanTamuController extends Controller
{
    /** Pertanyaan identik dari pengirim yang sama dalam rentang ini dianggap satu. */
    private const JEDA_DUPLIKAT_MENIT = 10;

    public function store(Request $request): Response
    {
        $data = $request->validate([
            'sumber' => ['required', Rule::in(PertanyaanTamu::SUMBER_AKTIF)],
            'pesan' => ['required', 'string', 'max:2000'],
            'journey' => ['nullable', 'string', 'max:255'],
            'lokasi_pickup' => ['nullable', 'string', 'max:255'],
            'tanggal_perjalanan' => ['nullable', 'date_format:Y-m-d'],
            'durasi' => ['nullable', 'string', 'max:60'],
            'jenis_perjalanan' => ['nullable', 'string', 'max:60'],
            'nama' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'string'],
        ]);

        // Kolom "website" disembunyikan dari manusia; bot yang mengisinya diabaikan tanpa pesan galat.
        if (filled($data['website'] ?? null)) {
            return response()->noContent();
        }

        $sidik = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        $sudahAda = PertanyaanTamu::query()
            ->where('sidik_pengirim', $sidik)
            ->where('pesan', $data['pesan'])
            ->where('created_at', '>=', now()->subMinutes(self::JEDA_DUPLIKAT_MENIT))
            ->exists();

        if ($sudahAda) {
            return response()->noContent();
        }

        $pertanyaan = PertanyaanTamu::query()->create([
            ...collect($data)->except(['website', 'whatsapp'])->all(),
            'whatsapp' => $this->nomorLokal($data['whatsapp'] ?? null),
            'sidik_pengirim' => $sidik,
        ]);

        User::query()
            ->whereRaw('LOWER(jabatan) IN (?, ?)', ['admin', 'admin operasional'])
            ->each(fn (User $admin) => $admin->notify(new PertanyaanTamuMasukNotification($pertanyaan)));

        return response()->noContent();
    }

    /** Nomor valid disimpan sebagai 08..., selain itu dikosongkan agar admin tidak menghubungi nomor salah. */
    private function nomorLokal(?string $nomor): ?string
    {
        $lokal = NomorWhatsApp::lokal($nomor);

        return preg_match('/^08[0-9]{7,12}$/', $lokal) === 1 ? $lokal : null;
    }
}
