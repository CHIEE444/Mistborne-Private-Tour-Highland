<?php

namespace App\Http\Controllers\Admin;

use App\Bantuan\KonversiGambar;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hotel\PerbaruiHotelRequest;
use App\Http\Requests\Admin\Hotel\SimpanHotelRequest;
use App\Models\Hotel;
use App\Models\Perjalanan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HotelController extends Controller
{
    /** Status pesanan yang masih menahan reservasi hotel. */
    private const STATUS_AKTIF = [BookingStatus::Dihubungi, BookingStatus::Payment, BookingStatus::HoldSchedule];

    /** "Urus Hotel" pindah menjadi "Selesaikan Hotel" (kartu dashboard + halaman lihat semua); alamat lama diarahkan ke sana. */
    public function urus(): RedirectResponse
    {
        return redirect()->route('admin.selesaikan-hotel.indeks');
    }

    public function index(Request $request): View
    {
        $status = $request->query('status', 'semua');
        $idJourney = $request->integer('journey');

        $daftar = Hotel::query()
            ->with('perjalanan:id,nama')
            ->withCount(['pesanan as reservasi_mendatang_count' => fn ($query) => $query
                ->whereIn('status', array_map(fn (BookingStatus $s): string => $s->value, self::STATUS_AKTIF))
                ->whereDate('hotel_check_in', '>=', today()->toDateString())])
            ->when($status === 'aktif', fn ($query) => $query->where('aktif', true))
            ->when($status === 'nonaktif', fn ($query) => $query->where('aktif', false))
            ->when($idJourney, fn ($query, int $id) => $query->whereHas('perjalanan', fn ($sub) => $sub->whereKey($id)))
            ->when($request->query('cari'), fn ($query, $cari) => $query->where(function ($sub) use ($cari): void {
                $sub->whereLike('nama', "%{$cari}%")->orWhereLike('area', "%{$cari}%");
            }))
            ->orderBy('area')
            ->orderBy('nama')
            ->get();

        $semua = Hotel::query()->get(['aktif']);

        return view('admin.hotel.indeks', [
            'daftar' => $daftar,
            'jumlah' => [
                'semua' => $semua->count(),
                'aktif' => $semua->where('aktif', true)->count(),
                'nonaktif' => $semua->where('aktif', false)->count(),
            ],
            'daftarJourney' => Perjalanan::query()->where('template_pesanan_kustom', false)->orderBy('nama')->get(['id', 'nama']),
            'journeyTanpaHotel' => Perjalanan::query()
                ->where('template_pesanan_kustom', false)
                ->where('tone', '<>', 'custom')
                ->whereDoesntHave('hotel', fn ($query) => $query->where('hotel.aktif', true))
                ->orderBy('nama')
                ->pluck('nama'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Hotel::class);

        return view('admin.hotel.buat', ['daftarJourney' => $this->daftarJourney()]);
    }

    public function store(SimpanHotelRequest $request): RedirectResponse
    {
        $hotel = Hotel::create($this->dataDariRequest($request));
        $hotel->perjalanan()->sync($request->validated('perjalanan_ids', []));
        $hotel->sinkronkanKapasitasDenganJourney();

        return redirect()->route('admin.hotel.indeks')->with('sukses', 'Hotel berhasil ditambahkan.');
    }

    public function show(Hotel $hotel): View
    {
        $this->authorize('view', $hotel);

        $reservasi = $hotel->pesanan()->whereNotNull('hotel_check_in');
        $statusAktif = array_map(fn (BookingStatus $s): string => $s->value, self::STATUS_AKTIF);

        return view('admin.hotel.detail', [
            'hotel' => $hotel->load('perjalanan:id,nama'),
            'statistik' => [
                'reservasi_mendatang' => (clone $reservasi)->whereIn('status', $statusAktif)->whereDate('hotel_check_in', '>=', today()->toDateString())->count(),
                'reservasi_selesai' => (clone $reservasi)->where('status', BookingStatus::Completed->value)->count(),
            ],
        ]);
    }

    public function edit(Hotel $hotel): View
    {
        $this->authorize('update', $hotel);

        return view('admin.hotel.ubah', [
            'hotel' => $hotel->load('perjalanan:id'),
            'daftarJourney' => $this->daftarJourney(),
        ]);
    }

    public function update(PerbaruiHotelRequest $request, Hotel $hotel): RedirectResponse
    {
        $hotel->update($this->dataDariRequest($request, $hotel));
        $hotel->perjalanan()->sync($request->validated('perjalanan_ids', []));
        $hotel->sinkronkanKapasitasDenganJourney();

        return redirect()->route('admin.hotel.indeks')->with('sukses', 'Data hotel diperbarui.');
    }

    public function destroy(Hotel $hotel): RedirectResponse
    {
        $this->authorize('delete', $hotel);

        $jumlah = $hotel->pesanan()
            ->whereIn('status', array_map(fn (BookingStatus $s): string => $s->value, self::STATUS_AKTIF))
            ->whereDate('hotel_check_out', '>=', today()->toDateString())
            ->count();

        if ($jumlah > 0) {
            return back()->withErrors(['hotel' => "Hotel ini masih dipakai pada {$jumlah} reservasi aktif. Ganti hotel pada pesanan tersebut dulu, atau ubah statusnya jadi Nonaktif."]);
        }

        $hotel->delete();

        return redirect()->route('admin.hotel.indeks')->with('sukses', 'Hotel dihapus.');
    }

    /**
     * @return Collection<int, Perjalanan>
     */
    private function daftarJourney()
    {
        return Perjalanan::query()->where('template_pesanan_kustom', false)->orderBy('nama')->get(['id', 'nama', 'maks_orang']);
    }

    /**
     * Data hotel tanpa daftar journey; foto baru dikonversi ke WebP dan menggantikan foto lama.
     *
     * @return array<string, mixed>
     */
    private function dataDariRequest(SimpanHotelRequest|PerbaruiHotelRequest $request, ?Hotel $hotelLama = null): array
    {
        $data = $request->safe()->except(['perjalanan_ids', 'foto', 'galeri_lama', 'galeri_baru']);
        $data['galeri'] = $this->galeriDariRequest($request, $hotelLama);
        $data['kapasitas_kamar'] = $hotelLama?->kapasitas_kamar ?? 2;

        if ($request->hasFile('foto')) {
            if ($hotelLama?->foto && str_starts_with($hotelLama->foto, '/storage/')) {
                Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $hotelLama->foto), '/'));
            }

            $data['foto'] = '/storage/'.KonversiGambar::sampul($request->file('foto'), 'hotel', lebar: 1600, tinggi: 1200, kualitas: 90);
        }

        return $data;
    }

    /**
     * Galeri hotel: foto lama dipertahankan (label bisa diubah) atau dihapus beserta filenya, lalu foto baru dikonversi ke WebP.
     *
     * @return list<array{foto: string, label: string}>
     */
    private function galeriDariRequest(SimpanHotelRequest|PerbaruiHotelRequest $request, ?Hotel $hotelLama): array
    {
        $baris = collect((array) $request->validated('galeri_lama', []))->keyBy('foto');
        $galeri = [];

        foreach ($hotelLama?->galeri ?? [] as $lama) {
            $kiriman = $baris->get($lama['foto']);

            if ($kiriman !== null && ! empty($kiriman['hapus'])) {
                Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $lama['foto']), '/'));

                continue;
            }

            $galeri[] = ['foto' => $lama['foto'], 'label' => $kiriman['label'] ?? $lama['label']];
        }

        foreach ((array) $request->validated('galeri_baru', []) as $baru) {
            $galeri[] = [
                'foto' => '/storage/'.KonversiGambar::sampul($baru['berkas'], 'hotel/galeri', lebar: 1600, tinggi: 1200, kualitas: 90),
                'label' => $baru['label'],
            ];
        }

        return $galeri;
    }
}
