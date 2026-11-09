<?php

namespace App\Layanan;

use App\Models\JadwalKeberangkatan;
use App\Models\Pesanan;
use Illuminate\Support\Carbon;

/**
 * Menghitung kuota terisi banyak jadwal sekaligus dengan satu query (mencegah N+1 di Kalender Trip).
 */
class KuotaLayanan
{
    /**
     * @param  iterable<JadwalKeberangkatan>  $daftarJadwal
     * @return array<string, int> kunci "perjalanan_id|Y-m-d" => jumlah pesanan journey itu yang berangkat di tanggal tersebut
     */
    public function terisiMassal(iterable $daftarJadwal): array
    {
        $jadwal = collect($daftarJadwal);

        if ($jadwal->isEmpty()) {
            return [];
        }

        $tanggal = $jadwal->pluck('tanggal')->map(fn ($t) => $t->toDateString());

        // tanggal_trip bertipe datetime: kelompokkan per DATE() di database dan pakai batas "< besok" agar tanggal terakhir ikut terhitung.
        $terisiPerKunci = Pesanan::query()
            ->terhitungKuota()
            ->whereIn('perjalanan_id', $jadwal->pluck('perjalanan_id')->unique())
            ->where('tanggal_trip', '>=', $tanggal->min())
            ->where('tanggal_trip', '<', Carbon::parse($tanggal->max())->addDay())
            ->selectRaw('perjalanan_id, DATE(tanggal_trip) as tgl, count(*) as jumlah')
            ->groupBy('perjalanan_id', 'tgl')
            ->get()
            ->mapWithKeys(fn ($baris) => [$baris->perjalanan_id.'|'.$baris->tgl => (int) $baris->jumlah]);

        return $jadwal->mapWithKeys(fn (JadwalKeberangkatan $item) => [
            $item->perjalanan_id.'|'.$item->tanggal->toDateString() => $terisiPerKunci->get($item->perjalanan_id.'|'.$item->tanggal->toDateString(), 0),
        ])->all();
    }

    /**
     * Terapkan hasil terisiMassal() ke tiap model JadwalKeberangkatan (lewat cache transient
     * di model) supaya penuh()/statusKuota() yang memanggil terisi() di belakang layar ikut
     * memakai angka yang sudah dihitung, bukan query ulang per baris.
     *
     * @param  iterable<JadwalKeberangkatan>  $daftarJadwal
     */
    public function terapkanKeDaftar(iterable $daftarJadwal): void
    {
        $peta = $this->terisiMassal($daftarJadwal);

        foreach ($daftarJadwal as $jadwal) {
            $jadwal->isiTerisiCache($peta[$jadwal->perjalanan_id.'|'.$jadwal->tanggal->toDateString()] ?? 0);
        }
    }
}
