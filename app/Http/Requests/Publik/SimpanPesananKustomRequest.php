<?php

namespace App\Http\Requests\Publik;

use App\Bantuan\AturanNama;
use App\Bantuan\AturanPenginapan;
use App\Bantuan\NomorWhatsApp;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SimpanPesananKustomRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'whatsapp' => NomorWhatsApp::lokal($this->input('whatsapp')),
            'nama' => AturanNama::rapikan($this->input('nama')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => AturanNama::aturan(),
            'whatsapp' => ['required', 'string', 'regex:/^08[0-9]{7,12}$/'],
            'destinasi_diinginkan' => ['required', 'string', 'min:3', 'max:255', 'not_regex:/[<>]/'],
            'tanggal_fleksibel' => ['nullable', 'boolean'],
            'tanggal_diinginkan' => ['nullable', 'required_unless:tanggal_fleksibel,1', 'date', 'after_or_equal:'.now()->addDays(7)->toDateString(), 'before_or_equal:'.now()->addDays(7)->addYear()->toDateString()],
            'durasi_hari' => ['required', 'integer', 'min:1', 'max:30'],
            'lokasi_penjemputan' => ['required', 'string', 'min:3', 'max:255', 'not_regex:/[<>]/'],
            ...AturanPenginapan::aturan(),
            'jumlah_orang' => ['required', 'integer', 'min:1', 'max:50'],
            'minat_perjalanan' => ['nullable', 'array', 'max:8'],
            'minat_perjalanan.*' => ['string', 'in:alam,budaya,kuliner,fotografi,keluarga,petualangan,sejarah,belanja'],
            'tempo_perjalanan' => ['nullable', 'string', 'in:santai,normal,aktif'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            // Honeypot: field tersembunyi di CSS. Sengaja TANPA aturan ketat — ditolak diam-diam
            // di controller (filled() check), bukan lewat error validasi, supaya bot tidak belajar.
            'website' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nama.min' => 'Nama lengkap minimal 3 karakter.',
            'nama.max' => 'Nama lengkap maksimal 255 karakter.',
            'nama.not_regex' => 'Nama tidak boleh memuat karakter < atau >.',
            'nama.regex' => 'Nama lengkap harus memuat huruf dan tidak boleh berisi simbol.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex' => 'Masukkan nomor WhatsApp dengan angka saja, diawali 08 atau +62, contoh 081234567890.',
            'destinasi_diinginkan.required' => 'Destinasi yang diinginkan wajib diisi.',
            'destinasi_diinginkan.min' => 'Destinasi minimal 3 karakter.',
            'destinasi_diinginkan.max' => 'Destinasi maksimal 255 karakter.',
            'destinasi_diinginkan.not_regex' => 'Destinasi tidak boleh memuat karakter < atau >.',
            'tanggal_diinginkan.required_unless' => 'Pilih tanggal perjalanan atau tandai tanggal masih fleksibel.',
            'tanggal_diinginkan.date' => 'Tanggal perjalanan tidak valid.',
            'tanggal_diinginkan.after_or_equal' => 'Tanggal perjalanan minimal 7 hari dari hari ini (H-7), sama seperti pesanan resmi, agar guide, mobil, dan pembayaran sempat disiapkan.',
            'tanggal_diinginkan.before_or_equal' => 'Tanggal perjalanan hanya dapat dipilih sampai satu tahun ke depan.',
            'durasi_hari.required' => 'Durasi perjalanan wajib diisi.',
            'durasi_hari.integer' => 'Durasi harus berupa angka bulat.',
            'durasi_hari.min' => 'Durasi minimal satu hari.',
            'durasi_hari.max' => 'Durasi maksimal 30 hari.',
            'lokasi_penjemputan.required' => 'Area penjemputan wajib diisi.',
            'lokasi_penjemputan.min' => 'Area penjemputan minimal 3 karakter.',
            'lokasi_penjemputan.max' => 'Area penjemputan maksimal 255 karakter.',
            'lokasi_penjemputan.not_regex' => 'Area penjemputan tidak boleh memuat karakter < atau >.',
            'catatan.max' => 'Catatan maksimal 1.000 karakter.',
            ...AturanPenginapan::pesan(),
            'jumlah_orang.required' => 'Jumlah orang wajib diisi.',
            'jumlah_orang.integer' => 'Jumlah orang harus berupa angka bulat.',
            'jumlah_orang.min' => 'Jumlah orang minimal satu.',
            'jumlah_orang.max' => 'Jumlah orang maksimal 50.',
        ];
    }

    /** Nomor WhatsApp dinormalisasi ke format 62xxxxxxxxxx sebelum disimpan. */
    public function whatsappTernormalisasi(): string
    {
        $digit = preg_replace('/\D/', '', (string) $this->input('whatsapp')) ?? '';

        if (str_starts_with($digit, '0')) {
            $digit = '62'.substr($digit, 1);
        }

        if (! str_starts_with($digit, '62')) {
            $digit = '62'.$digit;
        }

        return $digit;
    }
}
