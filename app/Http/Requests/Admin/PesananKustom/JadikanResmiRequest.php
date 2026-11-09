<?php

namespace App\Http\Requests\Admin\PesananKustom;

use App\Bantuan\AturanPenginapan;
use App\Models\PesananKustom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class JadikanResmiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pesananKustom = $this->route('pesananKustom');

        return $pesananKustom instanceof PesananKustom
            && $pesananKustom->pesanan_id === null
            && ($this->user()?->can('update', $pesananKustom) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tamu_menyetujui' => ['required', 'accepted'],
            'tanggal_trip' => ['required', 'date', 'after_or_equal:'.now()->addDays(7)->toDateString(), 'before_or_equal:'.now()->addDays(7)->addYear()->toDateString()],
            ...AturanPenginapan::aturan(),
            'lokasi_pickup' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $pesananKustom = $this->route('pesananKustom');
            if (! $pesananKustom instanceof PesananKustom) {
                return;
            }

            if ($pesananKustom->tanggal_fleksibel || ! $pesananKustom->tanggal_diinginkan || ! $this->filled('tanggal_trip')) {
                return;
            }

            try {
                $tanggalDipilih = Carbon::parse($this->input('tanggal_trip'))->toDateString();
            } catch (\Throwable) {
                return;
            }

            if ($tanggalDipilih !== $pesananKustom->tanggal_diinginkan->toDateString()) {
                $validator->errors()->add('tanggal_trip', 'Tanggal harus sama dengan tanggal yang disepakati. Perbarui data permintaan dahulu jika tanggal sudah berubah.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'tanggal_trip.after_or_equal' => 'Tanggal trip resmi minimal tujuh hari dari hari ini.',
            'tanggal_trip.before_or_equal' => 'Tanggal trip resmi maksimal satu tahun dari batas minimal pemesanan.',
            'lokasi_pickup.required' => 'Lokasi penjemputan final wajib diisi.',
            ...AturanPenginapan::pesan(),
            'tamu_menyetujui.required' => 'Konfirmasi persetujuan tamu sebelum membuat pesanan resmi.',
            'tamu_menyetujui.accepted' => 'Konfirmasi persetujuan tamu sebelum membuat pesanan resmi.',
        ];
    }
}
