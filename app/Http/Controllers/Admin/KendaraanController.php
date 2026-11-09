<?php

namespace App\Http\Controllers\Admin;

use App\Bantuan\KonversiGambar;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Kendaraan\PerbaruiKendaraanRequest;
use App\Http\Requests\Admin\Kendaraan\SimpanKendaraanRequest;
use App\Layanan\KetersediaanKendaraanLayanan;
use App\Layanan\RingkasanKendaraanLayanan;
use App\Models\Kendaraan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class KendaraanController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'semua');

        $daftar = Kendaraan::query()
            ->withCount(['pesanan as pesanan_aktif_count' => fn ($query) => $query->menahanKendaraan()])
            ->when(array_key_exists($status, Kendaraan::STATUS), fn ($query) => $query->where('status', $status))
            ->when($request->query('cari'), fn ($query, $cari) => $query->where(function ($sub) use ($cari): void {
                $sub->whereLike('nama', "%{$cari}%")->orWhereLike('plat_nomor', "%{$cari}%");
            }))
            ->orderBy('nama')
            ->get();

        $semua = Kendaraan::query()->get(['status']);

        return view('admin.kendaraan.indeks', [
            'daftar' => $daftar,
            'jumlah' => [
                'semua' => $semua->count(),
                'siap' => $semua->where('status', 'siap')->count(),
                'servis' => $semua->where('status', 'servis')->count(),
                'nonaktif' => $semua->where('status', 'nonaktif')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Kendaraan::class);

        return view('admin.kendaraan.buat');
    }

    public function store(SimpanKendaraanRequest $request): RedirectResponse
    {
        Kendaraan::create($this->dataDariRequest($request));

        return redirect()->route('admin.kendaraan.indeks')->with('sukses', 'Kendaraan berhasil ditambahkan.');
    }

    public function show(Kendaraan $kendaraan, RingkasanKendaraanLayanan $ringkasan): View
    {
        $this->authorize('view', $kendaraan);

        return view('admin.kendaraan.detail', [
            'kendaraan' => $kendaraan,
            'ringkasan' => $ringkasan->ringkas($kendaraan),
        ]);
    }

    public function edit(Kendaraan $kendaraan): View
    {
        $this->authorize('update', $kendaraan);

        return view('admin.kendaraan.ubah', ['kendaraan' => $kendaraan]);
    }

    public function update(PerbaruiKendaraanRequest $request, Kendaraan $kendaraan, KetersediaanKendaraanLayanan $ketersediaan): RedirectResponse
    {
        $kendaraan->update($this->dataDariRequest($request, $kendaraan));

        $kendaraan->refresh();
        $pesananBermasalah = $ketersediaan->penugasanBermasalah($kendaraan);
        $destinasiBermasalah = $ketersediaan->destinasiBermasalah($kendaraan);
        $pengalihan = redirect()->route('admin.kendaraan.indeks')->with('sukses', 'Data kendaraan diperbarui.');

        $peringatan = [];

        if ($pesananBermasalah->isNotEmpty()) {
            $rincian = $pesananBermasalah->take(5)->map(fn (array $butir): string => "{$butir['pesanan']->kode} ({$butir['alasan']})")->implode('; ');
            $peringatan[] = "{$pesananBermasalah->count()} pesanan yang sedang berjalan atau mendatang masih memakai {$kendaraan->plat_nomor} padahal kendaraannya tidak lagi layak: {$rincian}. Ganti kendaraan pada pesanan tersebut.";
        }

        if ($destinasiBermasalah->isNotEmpty()) {
            $rincian = $destinasiBermasalah->map(fn (array $butir): string => "{$butir['perjalanan']->nama} ({$butir['alasan']})")->implode('; ');
            $peringatan[] = "{$destinasiBermasalah->count()} destinasi masih memasang {$kendaraan->plat_nomor} sebagai mobil tetap padahal tidak lagi cocok: {$rincian}. Ganti mobil destinasi itu di menu Journey atau Paket Wisata.";
        }

        return $peringatan === [] ? $pengalihan : $pengalihan->with('peringatan', 'Perhatian: '.implode(' ', $peringatan));
    }

    public function destroy(Kendaraan $kendaraan): RedirectResponse
    {
        $this->authorize('delete', $kendaraan);

        $jumlahDestinasi = $kendaraan->perjalanan()->count();

        if ($jumlahDestinasi > 0) {
            return back()->withErrors(['kendaraan' => "Kendaraan ini masih dipasang sebagai mobil tetap pada {$jumlahDestinasi} destinasi. Ganti mobil destinasi itu dulu di menu Journey atau Paket Wisata, atau ubah statusnya jadi Nonaktif."]);
        }

        $jumlahPesanan = $kendaraan->pesanan()->menahanKendaraan()->count();

        if ($jumlahPesanan > 0) {
            return back()->withErrors(['kendaraan' => "Kendaraan ini masih ditugaskan pada {$jumlahPesanan} pesanan yang sedang berjalan atau mendatang. Ganti kendaraan pada pesanan tersebut dulu, atau ubah statusnya jadi Nonaktif."]);
        }

        $kendaraan->delete();

        return redirect()->route('admin.kendaraan.indeks')->with('sukses', 'Kendaraan dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function dataDariRequest(SimpanKendaraanRequest|PerbaruiKendaraanRequest $request, ?Kendaraan $kendaraanLama = null): array
    {
        $data = $request->safe()->except(['foto']);

        if ($request->hasFile('foto')) {
            if ($kendaraanLama?->foto && str_starts_with($kendaraanLama->foto, '/storage/')) {
                Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $kendaraanLama->foto), '/'));
            }

            $path = KonversiGambar::sampul($request->file('foto'), 'kendaraan', lebar: 1200, tinggi: 800);
            $data['foto'] = '/storage/'.$path;
        }

        return $data;
    }
}
