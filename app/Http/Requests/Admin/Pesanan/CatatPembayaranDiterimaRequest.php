<?php

namespace App\Http\Requests\Admin\Pesanan;

use App\Bantuan\FormatRupiah;
use App\Enums\BookingStatus;
use App\Models\Pesanan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatatPembayaranDiterimaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('pesanan')) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'jenis_pembayaran' => ['required', Rule::in(['uang_muka', 'lunas'])],
            'nominal_dibayar' => ['required', 'integer', 'min:1'],
            'metode_pembayaran' => ['required', Rule::in(['transfer', 'qris', 'tunai', 'lainnya'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nominal_dibayar.required' => 'Isi nominal transfer yang diterima sesuai bukti transfer.',
            'nominal_dibayar.integer' => 'Nominal pembayaran harus berupa angka bulat dalam rupiah.',
            'nominal_dibayar.min' => 'Nominal transfer baru harus lebih besar dari Rp 0.',
            'metode_pembayaran.required' => 'Pilih metode pembayaran yang diterima.',
            'jenis_pembayaran.required' => 'Pilih catat uang muka atau tamu sudah lunas.',
            'jenis_pembayaran.in' => 'Pilihan jenis pembayaran tidak valid. Silakan pilih ulang.',
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Pesanan $pesanan */
            $pesanan = $this->route('pesanan');

            if (! in_array($pesanan->status, [BookingStatus::Dihubungi, BookingStatus::HoldSchedule, BookingStatus::Payment], true)) {
                $validator->errors()->add('status', 'Pembayaran hanya bisa dicatat untuk pesanan aktif yang belum lunas.');
            }

            $totalTagihan = $pesanan->totalTagihan();
            if ($totalTagihan === null) {
                $validator->errors()->add('nominal_dibayar', 'Tentukan total harga pesanan sebelum mencatat pembayaran.');

                return;
            }

            if ($pesanan->status === BookingStatus::Payment && $pesanan->sisaTagihan() === 0) {
                $validator->errors()->add('status', 'Pesanan ini sudah lunas dan tidak menerima pencatatan pembayaran tambahan.');

                return;
            }

            $jenis = $this->input('jenis_pembayaran');
            $sudahDibayar = (int) ($pesanan->nominal_dibayar ?? 0);
            $nominalTransferBaru = (int) $this->input('nominal_dibayar');
            $sisaTagihan = max(0, $totalTagihan - $sudahDibayar);
            $tombolBayarSebagian = $pesanan->status === BookingStatus::Payment ? 'Catat Pembayaran' : 'Catat Uang Muka';

            // Tombol harus cocok dengan nominal: lunas hanya bila persis sama dengan sisa tagihan, sebagian hanya bila kurang.
            if ($nominalTransferBaru > $sisaTagihan) {
                $validator->errors()->add('nominal_dibayar', 'Nominal transfer melebihi sisa tagihan '.FormatRupiah::dari($sisaTagihan).'.');

                return;
            }

            if ($jenis === 'lunas' && $nominalTransferBaru < $sisaTagihan) {
                $validator->errors()->add('nominal_dibayar', 'Nominal '.FormatRupiah::dari($nominalTransferBaru).' kurang dari sisa tagihan '.FormatRupiah::dari($sisaTagihan).'. Untuk pembayaran sebagian, gunakan tombol '.$tombolBayarSebagian.'.');

                return;
            }

            if ($jenis === 'uang_muka' && $nominalTransferBaru === $sisaTagihan) {
                $validator->errors()->add('nominal_dibayar', 'Nominal '.FormatRupiah::dari($nominalTransferBaru).' sudah melunasi sisa tagihan. Gunakan tombol Tamu Sudah Lunas.');

                return;
            }

            $minimalUangMuka = (int) ceil($pesanan->harga * 0.3);
            if ($jenis === 'uang_muka' && $pesanan->status !== BookingStatus::Payment && $sudahDibayar + $nominalTransferBaru < $minimalUangMuka) {
                $validator->errors()->add('nominal_dibayar', 'Total pembayaran setelah transfer ini minimal 30% (Rp '.number_format($minimalUangMuka, 0, ',', '.').').');
            }
        }];
    }
}
