<?php

namespace App\Http\Requests\Admin\PesananKustom;

use App\Enums\StatusPesananKustom;
use App\Models\PesananKustom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PerbaruiTindakLanjutPesananKustomRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pesananKustom = $this->route('pesananKustom');

        return $pesananKustom instanceof PesananKustom
            && $pesananKustom->pesanan_id === null
            && ($this->user()?->can('update', $pesananKustom) ?? false);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            // Status Menunggu Jawaban hanya dapat dipasang lewat aksi khusus setelah
            // admin mengirim penawaran. Disetujui hanya lewat konversi atomik.
            // Tanpa status (formulir Susun Penawaran) controller memakai status sekarang, Baru menjadi Diproses.
            'status' => ['sometimes', 'required', Rule::in([
                StatusPesananKustom::Baru->value,
                StatusPesananKustom::Diproses->value,
                StatusPesananKustom::MenungguJawaban->value,
                StatusPesananKustom::TidakJadi->value,
            ])],
            'harga_final' => ['nullable', 'integer', 'min:1', 'max:'.PesananKustom::BATAS_HARGA_PER_ORANG],
            'nama_pendamping' => ['nullable', 'string', 'max:255'],
            'rencana_perjalanan' => ['nullable', 'string', 'max:10000'],
            'rincian_penawaran' => ['nullable', 'string', 'max:10000'],
            'batas_penawaran' => ['nullable', 'date'],
            'catatan_admin' => ['nullable', 'string', 'max:2000'],
            'alasan_tidak_jadi' => ['nullable', 'required_if:status,tidak_jadi', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $pesananKustom = $this->route('pesananKustom');
            if (! $pesananKustom instanceof PesananKustom || ! $this->filled('batas_penawaran')) {
                return;
            }

            try {
                $tanggalBaru = Carbon::parse($this->input('batas_penawaran'))->toDateString();
            } catch (\Throwable) {
                return;
            }

            $tanggalTersimpan = $pesananKustom->batas_penawaran?->toDateString();
            if ($tanggalBaru < today()->toDateString() && $tanggalBaru !== $tanggalTersimpan) {
                $validator->errors()->add('batas_penawaran', 'Masa berlaku penawaran baru tidak boleh sudah lewat.');
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'status.required' => 'Pilih status tindak lanjut.',
            'harga_final.integer' => 'Harga penawaran harus berupa angka bulat.',
            'harga_final.min' => 'Harga penawaran harus lebih besar dari Rp 0.',
            'harga_final.max' => 'Harga per orang melewati batas maksimal sistem.',
            'rencana_perjalanan.max' => 'Rencana perjalanan maksimal 10.000 karakter.',
            'rincian_penawaran.max' => 'Rincian penawaran maksimal 10.000 karakter.',
            'alasan_tidak_jadi.required_if' => 'Isi alasan sebelum menutup permintaan sebagai Tidak Jadi.',
            'alasan_tidak_jadi.max' => 'Alasan tidak jadi maksimal 2.000 karakter.',
        ];
    }
}
