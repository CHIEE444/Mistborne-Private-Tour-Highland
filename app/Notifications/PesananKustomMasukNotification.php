<?php

namespace App\Notifications;

use App\Models\PesananKustom;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PesananKustomMasukNotification extends Notification
{
    use Queueable;

    public function __construct(public PesananKustom $pesananKustom) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => 'pesanan_kustom_masuk',
            'judul' => 'Permintaan trip kustom baru',
            'pesan' => "{$this->pesananKustom->nama} merancang trip kustom ke {$this->pesananKustom->destinasi_diinginkan} untuk {$this->pesananKustom->jumlah_orang} orang ({$this->pesananKustom->jadwalDiinginkanFormat()}).",
            'pesanan_kustom_id' => $this->pesananKustom->id,
            'waktu' => now()->toIso8601String(),
        ];
    }
}
