<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Layanan\KapasitasTripLayanan;
use App\Layanan\KuotaLayanan;
use App\Layanan\RekapKeberangkatanLayanan;
use App\Models\JadwalKeberangkatan;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class KalenderTripController extends Controller
{
    public function index(Request $request, KuotaLayanan $kuotaLayanan, KapasitasTripLayanan $kapasitas): View
    {
        $filter = $request->validate([
            'bulan' => ['nullable', 'date_format:Y-m'],
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
            'journey' => ['nullable', 'integer', 'exists:perjalanan,id'],
        ]);

        $bulan = ($filter['bulan'] ?? null)
            ? Carbon::createFromFormat('!Y-m', $filter['bulan'])->startOfMonth()
            : now()->startOfMonth();

        $awalGrid = $bulan->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $akhirGrid = $bulan->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $semuaJadwalGrid = JadwalKeberangkatan::query()
            ->with('perjalanan')
            ->whereBetween('tanggal', [$awalGrid->toDateString(), $akhirGrid->toDateString()])
            ->when($filter['journey'] ?? null, fn ($q, $id) => $q->where('perjalanan_id', $id))
            ->orderBy('tanggal')
            ->get();

        // Hitung kuota terisi SEKALI untuk seluruh grid (bisa puluhan-ratusan chip per
        // bulan), bukan query per chip — lihat KuotaLayanan.
        $kuotaLayanan->terapkanKeDaftar($semuaJadwalGrid);

        $jadwalPerTanggal = $semuaJadwalGrid->groupBy(fn (JadwalKeberangkatan $jadwal) => $jadwal->tanggal->toDateString());

        // Angka harian = trip yang memegang guide pada hari itu (termasuk hari lanjutan trip beberapa hari) dibanding
        // jumlah guide siap, karena 1 guide memegang 1 trip sampai selesai. Inilah batas nyata semua journey per hari.
        $tripPerHari = $kapasitas->tripPerHari($awalGrid->toDateString(), $akhirGrid->toDateString());

        // Trip lebih dari sehari tetap tampil di setiap hari berlangsungnya (bukan hanya hari berangkat).
        // Hari berangkat sudah dihitung di "terisi"; di sini hanya hari lanjutan: setelah berangkat sampai tanggal selesai.
        $berlangsungPerTanggal = [];
        Pesanan::query()
            ->terhitungKuota()
            ->whereDate('tanggal_trip', '<=', $akhirGrid->toDateString())
            ->whereRaw('COALESCE(tanggal_selesai, tanggal_trip) >= ?', [$awalGrid->toDateString()])
            ->when($filter['journey'] ?? null, fn ($q, $id) => $q->where('perjalanan_id', $id))
            ->get(['id', 'tanggal_trip', 'tanggal_selesai'])
            ->each(function (Pesanan $pesanan) use (&$berlangsungPerTanggal, $awalGrid, $akhirGrid): void {
                $hari = $pesanan->tanggal_trip->copy()->addDay()->max($awalGrid->copy()->startOfDay());
                $akhir = ($pesanan->tanggal_selesai ?? $pesanan->tanggal_trip)->copy()->min($akhirGrid->copy()->startOfDay());

                for (; $hari->lessThanOrEqualTo($akhir); $hari->addDay()) {
                    $kunci = $hari->toDateString();
                    $berlangsungPerTanggal[$kunci] = ($berlangsungPerTanggal[$kunci] ?? 0) + 1;
                }
            });

        $minggu = [];
        $kursor = $awalGrid->copy();
        while ($kursor->lessThanOrEqualTo($akhirGrid)) {
            $pekanIni = [];
            for ($i = 0; $i < 7; $i++) {
                $jadwalHari = $jadwalPerTanggal->get($kursor->toDateString(), collect());
                $terisiHari = (int) ($tripPerHari[$kursor->toDateString()] ?? 0);
                $kuotaHari = $kapasitas->jumlahGuideSiap($kursor->toDateString());

                $pekanIni[] = [
                    'tanggal' => $kursor->copy(),
                    'dalamBulanIni' => $kursor->month === $bulan->month,
                    'jadwal' => $jadwalHari,
                    'terisi' => $terisiHari,
                    'berlangsung' => $berlangsungPerTanggal[$kursor->toDateString()] ?? 0,
                    'kuota' => $kuotaHari,
                    'status' => match (true) {
                        $jadwalHari->isNotEmpty() && $jadwalHari->every->diblok => 'diblokir',
                        $terisiHari >= $kuotaHari => 'penuh',
                        $terisiHari > 0 && $terisiHari === $kuotaHari - 1 => 'hampir_penuh',
                        default => 'tersedia',
                    },
                ];
                $kursor->addDay();
            }
            $minggu[] = $pekanIni;
        }

        $tanggalDipilih = ($filter['tanggal'] ?? null) ? Carbon::createFromFormat('!Y-m-d', $filter['tanggal']) : null;
        $pesananHariDipilih = $tanggalDipilih
            ? Pesanan::query()->terhitungKuota()->with(['perjalanan', 'kendaraan', 'hotel'])
                ->whereDate('tanggal_trip', '<=', $tanggalDipilih->toDateString())
                ->whereRaw('COALESCE(tanggal_selesai, tanggal_trip) >= ?', [$tanggalDipilih->toDateString()])
                ->when($filter['journey'] ?? null, fn ($q, $id) => $q->where('perjalanan_id', $id))
                ->orderBy('nama_perjalanan')->orderBy('waktu_pickup')->get()
            : collect();

        return view('admin.kalender-trip.indeks', [
            'bulan' => $bulan,
            'minggu' => $minggu,
            'tanggalDipilih' => $tanggalDipilih,
            'pesananHariDipilih' => $pesananHariDipilih,
            'daftarJourney' => Perjalanan::query()->where('template_pesanan_kustom', false)->orderBy('nama')->get(['id', 'nama']),
            'journeyTerpilih' => isset($filter['journey']) ? Perjalanan::query()->find($filter['journey']) : null,
        ]);
    }

    /** Rekap keberangkatan satu bulan kalender sebagai file PDF (A4 landscape), mengikuti filter journey kalender. */
    public function rekapPdf(Request $request, RekapKeberangkatanLayanan $rekap): Response
    {
        $filter = $request->validate([
            'bulan' => ['nullable', 'date_format:Y-m'],
            'journey' => ['nullable', 'integer', 'exists:perjalanan,id'],
        ]);
        $bulan = isset($filter['bulan'])
            ? Carbon::createFromFormat('!Y-m', $filter['bulan'])->startOfMonth()
            : now()->startOfMonth();

        return Pdf::loadView('cetak.rekap-keberangkatan', $rekap->bulanan($bulan, $filter['journey'] ?? null))
            ->setPaper('a4', 'landscape')
            // Hanya huruf yang dipakai yang ikut tertanam, agar file tetap kecil (tanpa ini ±1 MB karena font utuh).
            ->setOption('isFontSubsettingEnabled', true)
            ->download('Rekap-Keberangkatan-'.$bulan->translatedFormat('F-Y').'.pdf');
    }
}
