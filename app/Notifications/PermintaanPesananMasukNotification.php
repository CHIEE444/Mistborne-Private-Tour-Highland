<?php

namespace App\Notifications;

use App\Models\PermintaanPesanan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PermintaanPesananMasukNotification extends Notification
{
    use Queueable;

    public function __construct(public PermintaanPesanan $permintaan) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => 'permintaan_pesanan_masuk',
            'judul' => 'Permintaan pesanan baru',
            'pesan' => ($this->permintaan->nama_lengkap ?: 'Tamu').($this->permintaan->dariBookingWebsite() ? ' mengirim booking dari website' : ' mengirim permintaan')." untuk {$this->permintaan->perjalanan?->nama} pada ".$this->permintaan->tanggal_trip?->translatedFormat('d M Y').'.',
            'permintaan_pesanan_id' => $this->permintaan->id,
            'nama_tamu' => $this->permintaan->nama_lengkap,
            'nama_perjalanan' => $this->permintaan->perjalanan?->nama,
            'tanggal_trip' => $this->permintaan->tanggal_trip?->toDateString(),
            'waktu' => now()->toIso8601String(),
        ];
    }
}
