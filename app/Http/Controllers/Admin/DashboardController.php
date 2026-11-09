<?php

namespace App\Http\Controllers\Admin;

use App\Aksi\Pesanan\SinkronkanTahapPesanan;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Layanan\AlurReservasiHotel;
use App\Layanan\BatasBayarLayanan;
use App\Layanan\KapasitasTripLayanan;
use App\Layanan\RatingGoogleLayanan;
use App\Layanan\StatistikDashboardLayanan;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, StatistikDashboardLayanan $statistik, RatingGoogleLayanan $ratingGoogle, BatasBayarLayanan $batasBayar, AlurReservasiHotel $alurHotel, SinkronkanTahapPesanan $sinkron): View
    {
        $sinkron->handleBilaPerlu();

        $tahunGrafik = min(max($request->integer('tahun', now()->year), 2000), now()->year + 1);

        // Pengingat H-7/H-3/H-2: aturan tahap sama dengan detail pesanan (Pesanan::tahapPengingatBerlaku()).
        $pengingat = Pesanan::query()->pengingatBerjalan()->with(['perjalanan', 'hotel', 'kendaraan'])->orderBy('tanggal_trip')->get();
        $pengingatPerTahap = $pengingat->groupBy(fn (Pesanan $pesanan): string => (string) $pesanan->tahapPengingatBerlaku());

        return view('admin.dashboard.indeks', [
            'pesananBaru' => $statistik->pesananBaru(),
            'sudahPayment' => $statistik->sudahPayment(),
            'pesananKustomBaru' => $statistik->pesananKustomBaru(),
            'pendapatanBulanIni' => $statistik->pendapatanBulanIni(),
            'grafikPesanan' => $statistik->pesananPerBulan($tahunGrafik),
            'tahunGrafik' => $tahunGrafik,
            'statusBulanIni' => $statistik->statusBulanIni(),
            'pesananTerbaru' => $statistik->pesananTerbaru(),
            'journeyTerlaris' => $statistik->journeyTerlaris(),
            'pesananKustomTerbaru' => $statistik->pesananKustomTerbaru(),
            'perluDitindaklanjuti' => $statistik->perluDitindaklanjuti($batasBayar),
            'ratingGoogle' => $ratingGoogle->ambil(),
            'ratingTamu' => $statistik->ratingTamu(),
            'pengingatH7' => $pengingatPerTahap->get('h7', collect()),
            'pengingatH3' => $pengingatPerTahap->get('h3', collect()),
            'pengingatH2' => $pengingatPerTahap->get('h2', collect()),
            // Popup "Perlu perhatian": sudah lewat batas bayar (dibatalkan otomatis pada hari ke-5) dan trip H-3 ke bawah yang syaratnya belum beres.
            'lewatBatasBayar' => Pesanan::query()->with('penggantian')->where('status', BookingStatus::Dihubungi)->whereNotNull('dikonfirmasi_at')
                ->where(fn ($query) => $query->whereNull('nominal_dibayar')->orWhere('nominal_dibayar', 0))
                ->orderBy('dikonfirmasi_at')->get()->filter(fn (Pesanan $pesanan): bool => $batasBayar->sudahLewat($pesanan))->values(),
            // Hari yang trip aktifnya melebihi jumlah guide (guide dinonaktifkan, SIM habis, atau data lama): perlu tambah guide atau pindah jadwal.
            'kelebihanGuide' => app(KapasitasTripLayanan::class)->rentangKelebihanGuide(),
            'tripBelumSiap' => Pesanan::query()->with(['penggantian', 'hotel'])->where('status', BookingStatus::Payment)
                ->whereDate('tanggal_trip', '<=', today()->addDays(3)->toDateString())->orderBy('tanggal_trip')->get()
                ->filter(fn (Pesanan $pesanan): bool => $pesanan->syaratTripBelumTerpenuhi() !== [] && ! $pesanan->tripSudahBerakhir())->values(),
            // Isi popup pengingat saat dashboard dibuka: tahap berlaku yang belum dikirim.
            'pengingatBelumTerkirim' => $pengingat->reject(fn (Pesanan $pesanan): bool => $pesanan->pengingatSudahTerkirim((string) $pesanan->tahapPengingatBerlaku()))->values(),
            'peringatanH1' => Pesanan::query()->peringatanH1()->with(['perjalanan', 'hotel'])->orderBy('nama_lengkap')->get(),
            // Pindahan "Urus Hotel" dari menu Hotel: pesanan Pembayaran yang tahap reservasi hotelnya belum tuntas, trip terdekat dulu.
            'selesaikanHotel' => $alurHotel->antrean()->reject(fn (Pesanan $pesanan): bool => $alurHotel->sudahDiurus($pesanan))->values(),
        ]);
    }
}
