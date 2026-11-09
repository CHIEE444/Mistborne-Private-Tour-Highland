<?php

namespace App\Http\Controllers\Admin;

use App\Aksi\Perjalanan\BlokirTanggal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\JadwalKeberangkatan\PerbaruiJadwalRequest;
use App\Http\Requests\Admin\JadwalKeberangkatan\SimpanJadwalRequest;
use App\Layanan\KapasitasTripLayanan;
use App\Layanan\KuotaLayanan;
use App\Models\JadwalKeberangkatan;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JadwalKeberangkatanController extends Controller
{
    public function index(Perjalanan $perjalanan, KuotaLayanan $kuotaLayanan, KapasitasTripLayanan $kapasitas): View
    {
        $this->authorize('update', $perjalanan);

        $jadwal = $perjalanan->jadwal()->orderBy('tanggal')->get();
        $kuotaLayanan->terapkanKeDaftar($jadwal);

        return view('admin.perjalanan.tanggal-tersedia', [
            'perjalanan' => $perjalanan,
            'jadwal' => $jadwal,
            // Status nyata tiap tanggal yang bisa dipesan (H+7 ke atas): kuota journey dan guide bebas.
            'kapasitas' => $kapasitas->daftarTanggal($perjalanan)->keyBy('tanggal'),
            // Jumlah pesanan journey ini per tanggal (aturan yang sama dengan destroy): dialog hapus memberi tahu lebih awal bila tanggal tidak bisa dihapus.
            'pesananPerTanggal' => Pesanan::query()
                ->where('perjalanan_id', $perjalanan->id)
                ->selectRaw('DATE(tanggal_trip) as tgl, count(*) as jumlah')
                ->groupBy('tgl')
                ->pluck('jumlah', 'tgl'),
        ]);
    }

    public function store(SimpanJadwalRequest $request, Perjalanan $perjalanan): RedirectResponse
    {
        $tanggalBaru = $request->input('mode') === 'rentang'
            ? $request->date('tanggal_mulai')->daysUntil($request->date('tanggal_selesai'))
            : collect([$request->date('tanggal')]);

        $dibuat = 0;
        $sudahAda = 0;
        foreach ($tanggalBaru as $tanggal) {
            $jadwal = $perjalanan->jadwal()->firstOrCreate(
                ['tanggal' => $tanggal->toDateString()],
                ['kuota' => $request->integer('kuota') ?: null],
            );
            $jadwal->wasRecentlyCreated ? $dibuat++ : $sudahAda++;
        }

        if ($dibuat === 0) {
            return back()->withInput()->withErrors([
                $request->input('mode') === 'rentang' ? 'tanggal_mulai' : 'tanggal' => 'Semua tanggal yang dipilih sudah ada di daftar.',
            ]);
        }

        $catatan = $sudahAda > 0 ? " ({$sudahAda} tanggal dilewati karena sudah ada)" : '';

        return back()->with('sukses', "{$dibuat} tanggal baru ditambahkan{$catatan}.");
    }

    public function update(PerbaruiJadwalRequest $request, Perjalanan $perjalanan, JadwalKeberangkatan $tanggal): RedirectResponse
    {
        $this->authorize('update', $perjalanan);

        $tanggal->update(['kuota' => $request->integer('kuota') ?: null]);

        return back()->with('sukses', 'Kuota tanggal berhasil diperbarui.');
    }

    public function blokir(Perjalanan $perjalanan, JadwalKeberangkatan $tanggal, BlokirTanggal $aksi): RedirectResponse
    {
        $this->authorize('update', $perjalanan);

        $aksi->handle($tanggal, ! $tanggal->diblok);

        return back()->with('sukses', $tanggal->diblok ? 'Tanggal diblokir.' : 'Blokir tanggal dibuka.');
    }

    public function destroy(Perjalanan $perjalanan, JadwalKeberangkatan $tanggal): RedirectResponse
    {
        $this->authorize('update', $perjalanan);

        if (Pesanan::where('perjalanan_id', $perjalanan->id)->whereDate('tanggal_trip', $tanggal->tanggal)->exists()) {
            return back()->withErrors(['tanggal' => 'Tanggal ini sudah punya pesanan, tidak bisa dihapus.']);
        }

        $tanggal->delete();

        return back()->with('sukses', 'Tanggal dihapus.');
    }
}
