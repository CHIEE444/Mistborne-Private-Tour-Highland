<?php

namespace App\Notifications;

use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class TanggapanPenugasanNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Pesanan $pesanan,
        public PersonelOperasional $personel,
        public string $jawaban,
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
        $status = $this->jawaban === 'diterima' ? 'menerima' : 'menolak';

        return [
            'jenis' => 'tanggapan_penugasan',
            'judul' => 'Tanggapan penugasan',
            'pesan' => "{$this->personel->nama} ({$this->personel->peran}) {$status} tugas {$this->pesanan->kode}.",
            'pesanan_id' => $this->pesanan->id,
            'kode_pesanan' => $this->pesanan->kode,
            'nama_personel' => $this->personel->nama,
            'peran' => $this->personel->peran,
            'jawaban' => $this->jawaban,
            'waktu' => $this->waktu->toIso8601String(),
        ];
    }
}
