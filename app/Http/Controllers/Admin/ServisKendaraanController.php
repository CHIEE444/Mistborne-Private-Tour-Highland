<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Kendaraan\PerbaruiServisKendaraanRequest;
use App\Http\Requests\Admin\Kendaraan\SimpanServisKendaraanRequest;
use App\Models\Kendaraan;
use App\Models\ServisKendaraan;
use Illuminate\Http\RedirectResponse;

class ServisKendaraanController extends Controller
{
    public function store(SimpanServisKendaraanRequest $request, Kendaraan $kendaraan): RedirectResponse
    {
        $servis = $kendaraan->servis()->create($this->dataServis($request));
        $this->majukanOdometer($kendaraan, $servis);

        return $this->kembali($kendaraan, 'Catatan servis ditambahkan.');
    }

    public function update(PerbaruiServisKendaraanRequest $request, Kendaraan $kendaraan, ServisKendaraan $servis): RedirectResponse
    {
        $servis->update($this->dataServis($request));
        $this->majukanOdometer($kendaraan, $servis);

        return $this->kembali($kendaraan, 'Catatan servis diperbarui.');
    }

    public function destroy(Kendaraan $kendaraan, ServisKendaraan $servis): RedirectResponse
    {
        $this->authorize('update', $kendaraan);

        $servis->delete();

        return $this->kembali($kendaraan, 'Catatan servis dihapus.');
    }

    /**
     * Data servis; kolom yang tidak relevan dengan statusnya dikosongkan supaya tidak ada sisa data lama
     * (mis. servis yang ditandai selesai tidak lagi menyimpan jatuh tempo).
     *
     * @return array<string, mixed>
     */
    private function dataServis(SimpanServisKendaraanRequest|PerbaruiServisKendaraanRequest $request): array
    {
        $data = $request->validated();

        if ($data['status'] === ServisKendaraan::SELESAI) {
            return [...$data, 'jatuh_tempo_tanggal' => null, 'jatuh_tempo_odometer' => null];
        }

        return [...$data, 'tanggal_servis' => null, 'odometer_servis' => null, 'biaya' => null];
    }

    /** Odometer kendaraan ikut naik bila servis yang dikerjakan mencatat angka yang lebih tinggi. */
    private function majukanOdometer(Kendaraan $kendaraan, ServisKendaraan $servis): void
    {
        if ($servis->sudahSelesai() && $servis->odometer_servis !== null && $servis->odometer_servis > (int) $kendaraan->odometer) {
            $kendaraan->update(['odometer' => $servis->odometer_servis]);
        }
    }

    private function kembali(Kendaraan $kendaraan, string $pesan): RedirectResponse
    {
        return redirect()->route('admin.kendaraan.detail', $kendaraan)->with('sukses', $pesan);
    }
}
