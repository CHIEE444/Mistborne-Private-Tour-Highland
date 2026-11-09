<?php

namespace App\Aksi\Ulasan;

use App\Models\Perjalanan;
use App\Models\Ulasan;

/**
 * Setujui ulasan untuk tampil publik, lalu perbarui rating_label (rata-rata satu desimal)
 * dan reviews_count journey terkait dari ulasan yang tampilPublik() saja (D5).
 */
class SetujuiUlasan
{
    public function handle(Ulasan $ulasan): Ulasan
    {
        $ulasan->update(['disetujui' => true]);

        $this->perbaruiRatingJourney($ulasan);

        return $ulasan->refresh();
    }

    private function perbaruiRatingJourney(Ulasan $ulasan): void
    {
        $perjalanan = $ulasan->pesanan?->perjalanan;

        if (! $perjalanan instanceof Perjalanan) {
            return;
        }

        $ulasanTampil = Ulasan::query()
            ->tampilPublik()
            ->whereHas('pesanan', fn ($query) => $query->where('perjalanan_id', $perjalanan->id))
            ->get();

        $perjalanan->update([
            'rating_label' => $ulasanTampil->isNotEmpty() ? number_format($ulasanTampil->avg('rating'), 1) : null,
            'reviews_count' => $ulasanTampil->count() ?: null,
        ]);
    }
}
