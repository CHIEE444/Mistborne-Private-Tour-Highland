<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HotelController extends Controller
{
    /**
     * Satu hotel bisa melayani beberapa journey dan paket. Journey yang dibuka pertama kali (tombol kembali,
     * tombol Booking, dan pilihan bawaan panel booking) adalah journey asal pengunjung, dikirim lewat ?dari=slug.
     * Tanpa konteks itu, journey utama didahulukan dari paket, lalu urut nama. Slug yang bukan milik hotel diabaikan.
     */
    public function show(Request $request, Hotel $hotel): View
    {
        abort_unless($hotel->aktif, 404);

        $journey = $hotel->perjalanan()
            ->where('template_pesanan_kustom', false)
            ->orderByRaw("case when perjalanan.jenis = 'journey' then 0 else 1 end")
            ->orderBy('perjalanan.nama')
            ->get(['perjalanan.id', 'perjalanan.nama', 'perjalanan.slug', 'perjalanan.maks_orang']);

        $asal = $journey->firstWhere('slug', (string) $request->query('dari'));

        // Dibuka dari kartu journey/paket tertentu: panel booking hanya menawarkan journey itu, bukan destinasi lain.
        if ($asal !== null) {
            $journey = collect([$asal]);
        }

        $foto = collect($hotel->fotoUrl() ? [['url' => $hotel->fotoUrl(), 'label' => null]] : [])
            ->merge($hotel->galeriPublik())
            ->values();

        return view('pages.hotel-detail', [
            'hotel' => $hotel,
            'foto' => $foto,
            'daftarJourney' => $journey,
        ]);
    }
}
