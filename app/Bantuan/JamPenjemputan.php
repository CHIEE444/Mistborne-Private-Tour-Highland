<?php

namespace App\Bantuan;

/**
 * Merapikan isian jam jemput dari berbagai sumber (kolom TIME database "14:00:00", input time "14:00",
 * ketikan manual "14.00" atau "2:00 PM") menjadi satu format "H:i" sebelum divalidasi.
 */
class JamPenjemputan
{
    public static function normalisasi(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $jam = trim($nilai);

        if ($jam === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})\s*[:.]\s*(\d{2})(?:\s*[:.]\s*\d{2})?\s*(am|pm)?$/i', $jam, $bagian) !== 1) {
            return $jam;
        }

        $jamAngka = (int) $bagian[1];
        $menit = (int) $bagian[2];
        $penanda = strtolower($bagian[3] ?? '');

        if ($penanda !== '') {
            if ($jamAngka < 1 || $jamAngka > 12) {
                return $jam;
            }

            $jamAngka = ($jamAngka % 12) + ($penanda === 'pm' ? 12 : 0);
        }

        if ($jamAngka > 23 || $menit > 59) {
            return $jam;
        }

        return sprintf('%02d:%02d', $jamAngka, $menit);
    }

    /** Teks "14:00" untuk ditampilkan atau mengisi ulang input time dari nilai database. */
    public static function untukInput(mixed $nilai): ?string
    {
        $jam = self::normalisasi($nilai);

        return $jam !== null && preg_match('/^\d{2}:\d{2}$/', $jam) === 1 ? $jam : null;
    }
}
