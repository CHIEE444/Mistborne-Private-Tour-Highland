<?php

namespace App\Http\Requests\Admin\Pesanan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** Catatan transfer MISTBORNE ke hotel untuk paket all-inclusive: nominal, tanggal, dan bukti transfer. */
class SimpanPembayaranHotelRequest extends FormRequest
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
            'bayar_hotel_nominal' => ['required', 'integer', 'min:50000', 'max:500000000'],
            'bayar_hotel_tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:'.now()->subYear()->toDateString()],
            'bayar_hotel_bukti' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'bayar_hotel_nominal' => 'nominal transfer',
            'bayar_hotel_tanggal' => 'tanggal transfer',
            'bayar_hotel_bukti' => 'bukti transfer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bayar_hotel_nominal.required' => 'Isi nominal transfer ke hotel.',
            'bayar_hotel_nominal.integer' => 'Nominal transfer harus berupa angka, tanpa huruf.',
            'bayar_hotel_nominal.min' => 'Nominal transfer minimal Rp 50.000.',
            'bayar_hotel_nominal.max' => 'Nominal transfer terlalu besar. Periksa kembali angkanya.',
            'bayar_hotel_tanggal.required' => 'Isi tanggal transfer.',
            'bayar_hotel_tanggal.date_format' => 'Tanggal transfer tidak valid.',
            'bayar_hotel_tanggal.before_or_equal' => 'Tanggal transfer tidak boleh di masa depan.',
            'bayar_hotel_tanggal.after_or_equal' => 'Tanggal transfer terlalu lama. Periksa kembali tanggalnya.',
            'bayar_hotel_bukti.max' => 'Bukti transfer lebih dari 5 MB. Pilih file yang ukurannya maksimal 5 MB.',
            'bayar_hotel_bukti.mimes' => 'Bukti transfer harus berupa JPG, PNG, WEBP, atau PDF.',
            'bayar_hotel_bukti.file' => 'Bukti transfer tidak valid. Pilih ulang file yang akan diunggah.',
            'bayar_hotel_bukti.uploaded' => 'Bukti transfer lebih dari 5 MB atau gagal dibaca server. Pilih file maksimal 5 MB lalu coba lagi.',
        ];
    }
}
