<?php

namespace App\Http\Controllers\Publik;

use App\Bantuan\AturanPenginapan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Publik\SimpanPesananKustomRequest;
use App\Kontrak\PengirimWhatsApp;
use App\Layanan\PesanWhatsAppLayanan;
use App\Models\Pengaturan;
use App\Models\PesananKustom;
use App\Models\User;
use App\Notifications\PesananKustomMasukNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PesananKustomController extends Controller
{
    public function create(): View
    {
        return view('pages.rancang-trip-kustom');
    }

    public function store(SimpanPesananKustomRequest $request): RedirectResponse
    {
        // Honeypot terisi → bot. Jangan simpan, tapi tetap tampilkan halaman sukses palsu
        // supaya bot tidak belajar bahwa submission-nya ditolak.
        if ($request->filled('website')) {
            return redirect()->route('rancang-trip-kustom')->with('sukses', true);
        }

        $kustom = PesananKustom::create([
            'nama' => $request->string('nama')->value(),
            'whatsapp' => $request->whatsappTernormalisasi(),
            'destinasi_diinginkan' => $request->string('destinasi_diinginkan')->value(),
            'tanggal_fleksibel' => $request->boolean('tanggal_fleksibel'),
            'tanggal_diinginkan' => $request->boolean('tanggal_fleksibel') ? null : $request->date('tanggal_diinginkan'),
            'durasi_hari' => $request->integer('durasi_hari'),
            'lokasi_penjemputan' => $request->string('lokasi_penjemputan')->value(),
            ...AturanPenginapan::normalisasi($request->only(['status_penginapan', 'nama_hotel'])),
            'jadwal_diinginkan' => $request->boolean('tanggal_fleksibel')
                ? 'Tanggal fleksibel'
                : $request->date('tanggal_diinginkan')?->format('Y-m-d'),
            'jumlah_orang' => $request->integer('jumlah_orang'),
            'catatan' => $request->input('catatan'),
            'minat_perjalanan' => $request->input('minat_perjalanan', []),
            'tempo_perjalanan' => $request->input('tempo_perjalanan'),
        ]);

        User::query()
            ->whereRaw('LOWER(jabatan) IN (?, ?)', ['admin', 'admin operasional'])
            ->each(fn (User $admin) => $admin->notify(new PesananKustomMasukNotification($kustom)));

        $pesan = app(PesanWhatsAppLayanan::class)->permintaanTripKustom($kustom);

        $tautanWa = app(PengirimWhatsApp::class)->buatTautan(
            Pengaturan::current()->nomor_whatsapp_admin,
            $pesan,
        );

        // Kembali ke halaman yang sama dan tampilkan draft chat dulu: tamu sendiri yang membuka WhatsApp dan menekan kirim,
        // baru setelah itu halaman menyatakan data terkirim (lihat pages/rancang-trip-kustom.blade.php).
        return redirect()->route('rancang-trip-kustom')
            ->with('sukses', true)
            ->with('tautanWa', $tautanWa)
            ->with('pesanWa', $pesan);
    }
}
