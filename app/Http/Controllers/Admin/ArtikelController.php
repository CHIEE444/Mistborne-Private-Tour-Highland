<?php

namespace App\Http\Controllers\Admin;

use App\Bantuan\KonversiGambar;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Artikel\ArtikelRequest;
use App\Http\Requests\Admin\Artikel\PerbaruiArtikelRequest;
use App\Http\Requests\Admin\Artikel\SimpanArtikelRequest;
use App\Models\Artikel;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ArtikelController extends Controller
{
    public function index(): View
    {
        $statusTab = request('status', 'semua');

        $query = Artikel::query()
            ->when($statusTab === 'terbit', fn ($q) => $q->terbit())
            ->when($statusTab === 'draft', fn ($q) => $q->whereNull('published_at'))
            ->when($statusTab === 'dijadwalkan', fn ($q) => $q->whereNotNull('published_at')->where('published_at', '>', now()))
            ->when(request('cari'), fn ($q, $cari) => $q->whereLike('judul', "%{$cari}%"))
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');

        $semua = Artikel::query()->get();

        return view('admin.artikel.indeks', [
            'daftar' => $query->paginate(9)->withQueryString(),
            'jumlahTerbit' => $semua->filter(fn (Artikel $a) => $a->status() === 'terbit')->count(),
            'jumlahDraft' => $semua->filter(fn (Artikel $a) => $a->status() === 'draft')->count(),
            'jumlahDijadwalkan' => $semua->filter(fn (Artikel $a) => $a->status() === 'dijadwalkan')->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.artikel.buat');
    }

    public function store(SimpanArtikelRequest $request): RedirectResponse
    {
        $artikel = Artikel::create($this->dataDariRequest($request));

        return redirect()
            ->route('admin.artikel.indeks')
            ->with('sukses', $artikel->status() === 'draft' ? 'Draf artikel disimpan.' : 'Artikel berhasil disimpan.');
    }

    public function edit(Artikel $artikel): View
    {
        return view('admin.artikel.ubah', ['artikel' => $artikel]);
    }

    public function update(PerbaruiArtikelRequest $request, Artikel $artikel): RedirectResponse
    {
        $artikel->update($this->dataDariRequest($request, $artikel));

        return redirect()
            ->route('admin.artikel.indeks')
            ->with('sukses', $artikel->status() === 'draft' ? 'Draf artikel disimpan.' : 'Artikel berhasil diperbarui.');
    }

    public function destroy(Artikel $artikel): RedirectResponse
    {
        $this->authorize('delete', $artikel);

        $artikel->delete();

        return redirect()->route('admin.artikel.indeks')->with('sukses', 'Artikel dihapus.');
    }

    public function pulihkan(int $id): RedirectResponse
    {
        $artikel = Artikel::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $artikel);

        $artikel->restore();

        return back()->with('sukses', 'Artikel dipulihkan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function dataDariRequest(SimpanArtikelRequest|PerbaruiArtikelRequest $request, ?Artikel $artikelLama = null): array
    {
        $data = $request->safe()->except(['img', 'aksi']);

        $data['penulis'] = $artikelLama?->penulis ?? $request->user()?->name;

        // Draft tidak punya jadwal; terbitkan wajib bertanggal (divalidasi di ArtikelRequest).
        // Status terbit/dijadwalkan dihitung dari published_at vs waktu sekarang setiap kali
        // artikel diminta, jadi artikel otomatis tayang tepat pada tanggal & jam yang diset.
        $data['published_at'] = $request->input('aksi') === 'draft'
            ? null
            : Carbon::createFromFormat(ArtikelRequest::FORMAT_JADWAL, $request->string('published_at')->value(), config('app.timezone'));

        if ($request->hasFile('img')) {
            if ($artikelLama?->img && str_starts_with($artikelLama->img, '/storage/')) {
                Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $artikelLama->img), '/'));
            }

            // KonversiGambar::sampul() mengembalikan path relatif disk "public"; simpan sebagai URL "/storage/...".
            $path = KonversiGambar::sampul($request->file('img'), 'artikel/sampul');
            $data['img'] = '/storage/'.$path;
        }

        return $data;
    }
}
