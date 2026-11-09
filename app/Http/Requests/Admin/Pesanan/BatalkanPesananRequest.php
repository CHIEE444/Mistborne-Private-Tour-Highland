<?php

namespace App\Http\Requests\Admin\Pesanan;

use App\Aksi\Pesanan\BatalkanPesanan;
use App\Models\Pesanan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BatalkanPesananRequest extends FormRequest
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
            'pihak_pembatal' => ['required', Rule::in(array_keys(BatalkanPesanan::PIHAK))],
            'alasan_batal' => ['required', 'string', 'min:5', 'max:1000', 'not_regex:/[<>]/'],
            'nominal_refund' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'pihak_pembatal.required' => 'Pilih pihak yang membatalkan.',
            'pihak_pembatal.in' => 'Pilih pihak pembatal yang tersedia.',
            'alasan_batal.required' => 'Alasan pembatalan wajib diisi.',
            'alasan_batal.min' => 'Alasan pembatalan minimal 5 karakter.',
            'alasan_batal.not_regex' => 'Alasan pembatalan tidak boleh memuat karakter < atau >.',
            'alasan_batal.max' => 'Alasan pembatalan maksimal 1.000 karakter.',
            'nominal_refund.required' => 'Isi nominal refund, atau 0 bila tidak ada pengembalian dana.',
            'nominal_refund.integer' => 'Nominal refund harus berupa angka bulat dalam rupiah.',
            'nominal_refund.min' => 'Nominal refund tidak boleh kurang dari nol.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $pesanan = $this->route('pesanan');
            if ($pesanan instanceof Pesanan && (int) $this->input('nominal_refund') > (int) ($pesanan->nominal_dibayar ?? 0)) {
                $validator->errors()->add('nominal_refund', 'Nominal refund tidak boleh melebihi pembayaran yang sudah diterima.');
            }
        });
    }
}
