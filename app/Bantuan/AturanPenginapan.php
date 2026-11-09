<?php

namespace App\Bantuan;

use App\Enums\StatusPenginapan;
use Illuminate\Validation\Rule;

/**
 * Aturan, pesan, dan normalisasi isian penginapan untuk semua formulir: status wajib, nama hotel wajib bila "sudah punya".
 */
class AturanPenginapan
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function aturan(string $kolomNama = 'nama_hotel', string $kolomStatus = 'status_penginapan', bool $izinkanDiurus = false, bool $izinkanDipesankan = false, bool $wajibAlamat = false): array
    {
        $pilihan = [
            StatusPenginapan::Sudah->value,
            StatusPenginapan::Belum->value,
            ...($izinkanDiurus ? [StatusPenginapan::Diurus->value] : []),
            ...($izinkanDipesankan ? [StatusPenginapan::Dipesankan->value] : []),
        ];

        $kolomAlamat = 'alamat_hotel';

        return [
            $kolomStatus => ['required', Rule::in($pilihan)],
            $kolomNama => ["required_if:{$kolomStatus},".StatusPenginapan::Sudah->value, 'nullable', 'string', 'min:3', 'max:255', 'not_regex:/[<>]/'],
            // Alamat dipakai admin merinci trip dari penjemputan di hotel sampai selesai; wajib di formulir tamu.
            $kolomAlamat => [
                ...($wajibAlamat ? ["required_if:{$kolomStatus},".StatusPenginapan::Sudah->value] : []),
                'nullable', 'string', 'min:5', 'max:500', 'not_regex:/[<>]/',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function pesan(string $kolomNama = 'nama_hotel', string $kolomStatus = 'status_penginapan'): array
    {
        return [
            "{$kolomStatus}.required" => __('Pilih status penginapan: sudah punya atau belum.'),
            "{$kolomStatus}.in" => __('Status penginapan tidak valid.'),
            "{$kolomNama}.required_if" => __('Nama hotel atau penginapan wajib diisi bila Anda sudah punya penginapan.'),
            "{$kolomNama}.min" => __('Nama hotel atau penginapan minimal 3 karakter.'),
            "{$kolomNama}.max" => __('Nama hotel maksimal 255 karakter.'),
            "{$kolomNama}.not_regex" => __('Nama hotel tidak boleh memuat karakter < atau >.'),
            "{$kolomNama}.string" => __('Nama hotel atau penginapan harus berupa teks.'),
            'alamat_hotel.required_if' => __('Alamat hotel wajib diisi bila Anda sudah punya penginapan.'),
            'alamat_hotel.min' => __('Alamat hotel minimal 5 karakter.'),
            'alamat_hotel.max' => __('Alamat hotel maksimal 500 karakter.'),
            'alamat_hotel.not_regex' => __('Alamat hotel tidak boleh memuat karakter < atau >.'),
            'alamat_hotel.string' => __('Alamat hotel harus berupa teks.'),
        ];
    }

    /**
     * Bila belum punya penginapan, nama penginapan dikosongkan supaya data tidak bertentangan.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalisasi(array $data, string $kolomNama = 'nama_hotel', string $kolomStatus = 'status_penginapan', bool $dariTamu = true): array
    {
        // Belum punya: tidak ada nama. Diurus MISTBORNE: nama baru diisi admin setelah hotel dipesan, bukan oleh tamu.
        $kosongkan = $dariTamu
            ? [StatusPenginapan::Belum->value, StatusPenginapan::Diurus->value]
            : [StatusPenginapan::Belum->value];

        if (in_array($data[$kolomStatus] ?? null, $kosongkan, true)) {
            $data[$kolomNama] = null;
            $data['alamat_hotel'] = null;
        }

        return $data;
    }
}
