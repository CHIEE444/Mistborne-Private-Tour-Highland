<?php

namespace App\Notifications;

use App\Models\PertanyaanTamu;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PertanyaanTamuMasukNotification extends Notification
{
    use Queueable;

    public function __construct(public PertanyaanTamu $pertanyaan) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $tanggal = $this->pertanyaan->tanggal_perjalanan?->translatedFormat('d M Y') ?? 'tanggal belum dipilih';
        $penanya = $this->pertanyaan->nama ?: 'Calon tamu';

        return [
            'jenis' => 'pertanyaan_tamu_masuk',
            'judul' => 'Pertanyaan tamu baru',
            'pesan' => "{$penanya} bertanya ({$this->pertanyaan->labelSumber()}): ".($this->pertanyaan->journey ?: 'journey belum dipilih').", {$tanggal}.",
            'pertanyaan_tamu_id' => $this->pertanyaan->id,
            'waktu' => now()->toIso8601String(),
        ];
    }
}
