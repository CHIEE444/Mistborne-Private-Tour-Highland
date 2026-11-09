<?php

namespace App\Notifications;

use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class TripSelesaiNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Pesanan $pesanan,
        public PersonelOperasional $personel,
        public Carbon $waktu,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $jamJemput = $this->pesanan->waktu_pickup
            ? substr((string) $this->pesanan->waktu_pickup, 0, 5).' WIB'
            : 'belum ditentukan';
        $lokasiJemput = $this->pesanan->lokasi_pickup ?: 'belum ditentukan';
        $tanggalTrip = $this->pesanan->tanggal_trip->translatedFormat('d M Y');

        return [
            'jenis' => 'trip_selesai',
            'judul' => 'Trip selesai',
            'pesan' => "{$this->personel->nama} ({$this->personel->peran}) menyelesaikan trip {$this->pesanan->kode} menuju {$this->pesanan->nama_perjalanan} untuk rombongan {$this->pesanan->nama_lengkap} ({$this->pesanan->jumlah_wisatawan} orang), tanggal {$tanggalTrip}; jemput {$jamJemput} di {$lokasiJemput}.",
            'pesanan_id' => $this->pesanan->id,
            'kode_pesanan' => $this->pesanan->kode,
            'nama_personel' => $this->personel->nama,
            'peran' => $this->personel->peran,
            'nama_perjalanan' => $this->pesanan->nama_perjalanan,
            'nama_rombongan' => $this->pesanan->nama_lengkap,
            'jumlah_wisatawan' => $this->pesanan->jumlah_wisatawan,
            'tanggal_trip' => $this->pesanan->tanggal_trip->toDateString(),
            'waktu_jemput' => $jamJemput,
            'lokasi_jemput' => $this->pesanan->lokasi_pickup,
            'waktu' => $this->waktu->toIso8601String(),
        ];
    }
}
