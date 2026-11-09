<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Layanan\KapasitasTripLayanan;
use App\Models\Perjalanan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JourneyController extends Controller
{
    private const LOCALE_TERSEDIA = ['id', 'en', 'ms', 'zh', 'ja', 'ko', 'es', 'fr', 'ar'];

    /**
     * Parameter rute dibaca lewat nama, bukan posisi: rute berawalan bahasa berurutan {locale}/{slug},
     * sedangkan rute biasa hanya {slug}, sehingga argumen posisional akan tertukar.
     */
    public function show(Request $request, KapasitasTripLayanan $kapasitas): View
    {
        $slug = (string) $request->route('slug');
        $locale = $request->route('locale');
        $locale = is_string($locale) ? $locale : null;

        // Tanpa awalan bahasa di URL, bahasa pilihan tamu (sesi, lihat PertahankanBahasaPublik) tidak boleh ditimpa.
        if ($locale !== null) {
            abort_unless(in_array($locale, self::LOCALE_TERSEDIA, true), 404);
            app()->setLocale($locale);
        }

        $perjalanan = Perjalanan::query()
            ->with('kendaraan')
            ->where('template_pesanan_kustom', false)
            ->where('slug', $slug)
            ->firstOrFail();

        return view('pages.journey-detail', [
            'journey' => $perjalanan->kePublik(),
            'hotel' => $perjalanan->hotelPublik(),
            // Sama dengan kolom tanggal di formulir booking: Tanggal Tersedia beserta statusnya (penuh tetap tampil, ditandai).
            'jadwal' => $kapasitas->daftarTanggal($perjalanan)->reject(fn (array $opsi): bool => in_array($opsi['status'], ['ditutup', 'di_luar_batas'], true))->take(12)->values(),
        ]);
    }
}
