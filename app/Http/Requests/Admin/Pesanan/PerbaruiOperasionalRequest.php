<?php

namespace App\Http\Requests\Admin\Pesanan;

use App\Layanan\KetersediaanKendaraanLayanan;
use App\Layanan\KetersediaanPersonelOperasionalLayanan;
use App\Models\Kendaraan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PerbaruiOperasionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('pesanan')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'guide_personel_id' => ['nullable', 'integer', 'exists:personel_operasional,id'],
            'kendaraan_id' => ['nullable', 'integer', Rule::exists('kendaraan', 'id')->whereNull('deleted_at')],
            'catatan_admin' => ['nullable', 'string', 'max:2000'],
            'bukti_pembayaran' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'kendaraan_id.exists' => 'Kendaraan yang dipilih tidak ditemukan. Pilih dari daftar.',
            'kendaraan_id.integer' => 'Kendaraan yang dipilih tidak valid.',
            'bukti_pembayaran.max' => 'Lampiran lebih dari 5 MB. Pilih file yang ukurannya maksimal 5 MB.',
            'bukti_pembayaran.mimes' => 'Lampiran bukti pembayaran harus berupa JPG, PNG, atau PDF.',
            'bukti_pembayaran.file' => 'Lampiran bukti pembayaran tidak valid. Pilih ulang file yang akan diunggah.',
            'bukti_pembayaran.uploaded' => 'Lampiran lebih dari 5 MB atau gagal dibaca server. Pilih file yang ukurannya maksimal 5 MB lalu coba lagi.',
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->periksaKendaraan($validator);

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Pesanan $pesanan */
            $pesanan = $this->route('pesanan');
            $layanan = app(KetersediaanPersonelOperasionalLayanan::class);
            $field = 'guide_personel_id';
            $personelId = $this->integer($field);

            if (! $personelId) {
                return;
            }

            $samaDenganPilihanSaatIni = $personelId === (int) $pesanan->{$field};
            $personel = PersonelOperasional::query()
                ->where('peran', 'guide')
                ->whereKey($personelId)
                ->where('aktif', true)
                ->first();

            if (! $personel && ! $samaDenganPilihanSaatIni) {
                $validator->errors()->add($field, 'Pilih guide aktif dari daftar yang tersedia.');

                return;
            }

            if ($personel && ! $samaDenganPilihanSaatIni && $personel->status_operasional !== 'siap') {
                $validator->errors()->add($field, 'Guide sedang bertugas dan tidak bisa dipilih untuk jadwal ini.');

                return;
            }

            if (! $samaDenganPilihanSaatIni && $layanan->memilikiJadwalBentrok($personelId, $pesanan)) {
                $validator->errors()->add($field, 'Guide memiliki pesanan aktif lain pada rentang tanggal tersebut.');

                return;
            }

            $akhirTrip = $pesanan->tanggal_selesai ?? $pesanan->tanggal_trip;
            if ($personel && ! $samaDenganPilihanSaatIni && ! $personel->simBerlakuSampai($akhirTrip)) {
                $validator->errors()->add($field, $personel->masa_berlaku_sim
                    ? 'SIM guide habis sebelum trip selesai. Perbarui data SIM di menu Guide atau pilih guide lain.'
                    : 'Data SIM guide belum diisi. Lengkapi dulu di menu Guide agar bisa ditugaskan sebagai driver.');
            }
        }];
    }

    /** Kendaraan baru harus siap, cukup kursi, dan tidak bentrok; pilihan yang tidak berubah dibiarkan. */
    private function periksaKendaraan(Validator $validator): void
    {
        $kendaraanId = $this->integer('kendaraan_id');
        /** @var Pesanan $pesanan */
        $pesanan = $this->route('pesanan');

        if (! $kendaraanId || $kendaraanId === (int) $pesanan->kendaraan_id) {
            return;
        }

        $kendaraan = Kendaraan::query()->find($kendaraanId);
        $alasan = $kendaraan ? app(KetersediaanKendaraanLayanan::class)->alasanTidakLayak($kendaraan, $pesanan) : 'kendaraan tidak ditemukan';

        if ($alasan !== null) {
            $validator->errors()->add('kendaraan_id', 'Kendaraan tidak bisa dipilih: '.$alasan.'.');
        }
    }
}
