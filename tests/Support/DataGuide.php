<?php

namespace Tests\Support;

/**
 * Data kemampuan mengemudi yang sah untuk guide (guide merangkap driver): SIM berlaku, golongan, pengalaman,
 * dan kendaraan yang dikuasai. Dipakai tes yang membuat atau menugaskan guide.
 */
class DataGuide
{
    /**
     * Atribut model PersonelOperasional.
     *
     * @return array<string, mixed>
     */
    public static function sim(): array
    {
        return [
            'nomor_sim' => 'A 1234 5678 9012',
            'jenis_sim' => 'A',
            'masa_berlaku_sim' => now()->addYears(2)->toDateString(),
            'pengalaman_mengemudi_tahun' => 5,
            'kendaraan_dikuasai' => 'MPV, SUV',
        ];
    }

    /**
     * Isian formulir admin (kendaraan_dikuasai berupa larik pilihan).
     *
     * @return array<string, mixed>
     */
    public static function form(): array
    {
        return [...self::sim(), 'kendaraan_dikuasai' => ['MPV', 'SUV']];
    }
}
