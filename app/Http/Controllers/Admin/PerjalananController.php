<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Perjalanan\PerbaruiPerjalananRequest;
use App\Http\Requests\Admin\Perjalanan\SimpanPerjalananRequest;
use App\Models\Kendaraan;
use App\Models\Perjalanan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PerjalananController extends Controller
{
    public function index(): View
    {
        $jenis = $this->jenisDariQuery();

        $perjalanan = Perjalanan::query()
            ->where('jenis', $jenis)
            ->where('template_pesanan_kustom', false)
            ->when(request('cari'), fn ($query, $cari) => $query->whereLike('nama', "%{$cari}%"))
            ->when(request('region'), fn ($query, $region) => $query->where('region', $region))
            ->when(request('beranda') === 'ya', fn ($query) => $query->where('tampil_beranda', true))
            ->when(request('beranda') === 'tidak', fn ($query) => $query->where('tampil_beranda', false))
            ->withCount('jadwal')
            ->orderBy('urutan')
            ->paginate(12)
            ->withQueryString();

        return view('admin.perjalanan.indeks', [
            'perjalanan' => $perjalanan,
            'jenis' => $jenis,
            'daftarRegion' => Perjalanan::daftarRegion(),
        ]);
    }

    public function create(): View
    {
        return view('admin.perjalanan.buat', ['jenis' => $this->jenisDariQuery(), 'daftarKendaraan' => $this->pilihanKendaraan()]);
    }

    /**
     * Pilihan mobil tetap destinasi (id => label). Kendaraan yang sedang servis atau nonaktif tetap muncul dengan penanda statusnya.
     *
     * @return array<int, string>
     */
    private function pilihanKendaraan(): array
    {
        return Kendaraan::query()->orderBy('nama')->get()
            ->mapWithKeys(fn (Kendaraan $kendaraan): array => [
                $kendaraan->id => "{$kendaraan->namaUntukTamu()} · {$kendaraan->plat_nomor} · {$kendaraan->kapasitas} kursi".($kendaraan->status === 'siap' ? '' : ' · '.$kendaraan->labelStatus()),
            ])
            ->all();
    }

    /** Journey dan Paket Wisata memakai modul yang sama; jenis dibawa lewat ?jenis=paket (bawaan: journey). */
    private function jenisDariQuery(): string
    {
        return request()->query('jenis') === Perjalanan::JENIS_PAKET ? Perjalanan::JENIS_PAKET : Perjalanan::JENIS_JOURNEY;
    }

    /** @return array<string, string> */
    private function parameterJenis(string $jenis): array
    {
        return $jenis === Perjalanan::JENIS_PAKET ? ['jenis' => Perjalanan::JENIS_PAKET] : [];
    }

    private function namaJenis(string $jenis): string
    {
        return $jenis === Perjalanan::JENIS_PAKET ? 'Paket' : 'Journey';
    }

    public function store(SimpanPerjalananRequest $request): RedirectResponse
    {
        $perjalanan = DB::transaction(function () use ($request) {
            $perjalanan = Perjalanan::create($this->dataDariRequest($request));

            $perjalanan->jadwal()->create([
                'tanggal' => $request->date('tanggal_pertama'),
                'kuota' => $request->filled('kuota_pertama') ? $request->integer('kuota_pertama') : null,
            ]);

            return $perjalanan;
        });

        return redirect()
            ->route('admin.perjalanan.indeks', $this->parameterJenis($perjalanan->jenis))
            ->with('sukses', "{$this->namaJenis($perjalanan->jenis)} \"{$perjalanan->nama}\" berhasil dibuat.");
    }

    public function edit(Perjalanan $perjalanan): View
    {
        return view('admin.perjalanan.ubah', ['perjalanan' => $perjalanan, 'daftarKendaraan' => $this->pilihanKendaraan()]);
    }

    public function update(PerbaruiPerjalananRequest $request, Perjalanan $perjalanan): RedirectResponse
    {
        $perjalanan->update($this->dataDariRequest($request));
        $perjalanan->hotel->each->sinkronkanKapasitasDenganJourney();

        return redirect()
            ->route('admin.perjalanan.indeks', $this->parameterJenis($perjalanan->jenis))
            ->with('sukses', "{$this->namaJenis($perjalanan->jenis)} \"{$perjalanan->nama}\" berhasil diperbarui.");
    }

    public function destroy(Perjalanan $perjalanan): RedirectResponse
    {
        $this->authorize('delete', $perjalanan);

        $perjalanan->delete();

        return redirect()
            ->route('admin.perjalanan.indeks', $this->parameterJenis($perjalanan->jenis))
            ->with('sukses', "{$this->namaJenis($perjalanan->jenis)} \"{$perjalanan->nama}\" dihapus.");
    }

    /** Fallback dari Dialog Proteksi Hapus ketika penghapusan diblokir (punya pesanan aktif). */
    public function sembunyikanDariBeranda(Perjalanan $perjalanan): RedirectResponse
    {
        $this->authorize('update', $perjalanan);

        $perjalanan->update(['tampil_beranda' => false]);

        return redirect()
            ->route('admin.perjalanan.indeks', $this->parameterJenis($perjalanan->jenis))
            ->with('sukses', "{$this->namaJenis($perjalanan->jenis)} \"{$perjalanan->nama}\" disembunyikan dari beranda.");
    }

    /**
     * @return array<string, mixed>
     */
    private function dataDariRequest(SimpanPerjalananRequest|PerbaruiPerjalananRequest $request): array
    {
        $data = $request->safe()->except(['img', 'hapus_img', 'tanggal_pertama', 'kuota_pertama', 'highlights', 'itinerary', 'praktis']);

        $data['urutan'] = $request->integer('urutan');
        if ($request->route('perjalanan') instanceof Perjalanan) {
            unset($data['jenis']);
        } else {
            $data['jenis'] = ($data['jenis'] ?? null) === Perjalanan::JENIS_PAKET ? Perjalanan::JENIS_PAKET : Perjalanan::JENIS_JOURNEY;
        }
        $data['tampil_beranda'] = $request->boolean('tampil_beranda');
        if (array_key_exists('kendaraan_id', $data) && blank($data['kendaraan_id'])) {
            $data['kendaraan_id'] = null;
            $data['alasan_kendaraan'] = null;
        }
        $data['highlights'] = collect($request->input('highlights', []))->filter()->values()->all();
        $data['itinerary'] = collect($request->input('itinerary', []))
            ->filter(fn ($baris) => filled($baris['t'] ?? null) && filled($baris['x'] ?? null))
            ->values()
            ->all();
        $data['praktis'] = collect($request->input('praktis', []))->filter()->all();

        $perjalananLama = $request->route('perjalanan');
        $perjalananLama = $perjalananLama instanceof Perjalanan ? $perjalananLama : null;

        if ($request->hasFile('img')) {
            $perjalananLama?->hapusBerkasSampul();

            $data['img'] = '/storage/'.$request->file('img')->store('perjalanan/sampul', 'public');
        } elseif ($request->boolean('hapus_img') && $perjalananLama) {
            $perjalananLama->hapusBerkasSampul();

            $data['img'] = null;
        }

        return $data;
    }
}
