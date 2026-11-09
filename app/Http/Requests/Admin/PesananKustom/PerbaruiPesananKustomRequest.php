<?php

namespace App\Http\Requests\Admin\PesananKustom;

use App\Bantuan\AturanNama;
use App\Bantuan\AturanPenginapan;
use App\Bantuan\NomorWhatsApp;
use App\Models\PesananKustom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class PerbaruiPesananKustomRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['nama' => AturanNama::rapikan($this->input('nama'))]);
        $this->merge(['whatsapp' => NomorWhatsApp::lokal($this->input('whatsapp'))]);
    }

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
            'nama' => AturanNama::aturan(),
            'whatsapp' => ['required', 'string', 'regex:/^(?:08[0-9]{7,12}|628[0-9]{7,12})$/'],
            'destinasi_diinginkan' => ['required', 'string', 'max:255'],
            'tanggal_fleksibel' => ['nullable', 'boolean'],
            'tanggal_diinginkan' => ['nullable', 'required_unless:tanggal_fleksibel,1', 'date', function (string $attribute, mixed $value, \Closure $gagal): void {
                $tersimpan = $this->route('pesananKustom')?->tanggal_diinginkan?->toDateString();
                if ($value && $tersimpan !== Carbon::parse($value)->toDateString() && Carbon::parse($value)->lt(today()->addDays(7))) {
                    $gagal('Tanggal perjalanan minimal 7 hari dari hari ini (H-7), sama seperti pesanan resmi, agar guide, mobil, dan pembayaran sempat disiapkan.');
                }
            }],
            'durasi_hari' => ['required', 'integer', 'min:1', 'max:30'],
            'lokasi_penjemputan' => ['required', 'string', 'max:255'],
            ...AturanPenginapan::aturan(),
            'jumlah_orang' => ['required', 'integer', 'min:1', 'max:50'],
            'minat_perjalanan' => ['nullable', 'array', 'max:8'],
            'minat_perjalanan.*' => ['string', 'in:alam,budaya,kuliner,fotografi,keluarga,petualangan,sejarah,belanja'],
            'tempo_perjalanan' => ['nullable', 'string', 'in:santai,normal,aktif'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama tamu wajib diisi.',
            ...AturanNama::pesan('nama', 'Nama'),
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex' => 'Gunakan nomor WhatsApp yang diawali 08 atau 628, contoh 081234567890.',
            'destinasi_diinginkan.required' => 'Destinasi yang diinginkan wajib diisi.',
            'tanggal_diinginkan.required_unless' => 'Isi tanggal perjalanan atau tandai bahwa tanggal masih fleksibel.',
            'durasi_hari.required' => 'Durasi perjalanan wajib diisi.',
            'durasi_hari.integer' => 'Durasi harus berupa angka bulat.',
            'durasi_hari.min' => 'Durasi minimal satu hari.',
            'durasi_hari.max' => 'Durasi maksimal 30 hari.',
            'lokasi_penjemputan.required' => 'Area penjemputan wajib diisi.',
            ...AturanPenginapan::pesan(),
            'jumlah_orang.required' => 'Jumlah orang wajib diisi.',
            'jumlah_orang.integer' => 'Jumlah orang harus berupa angka bulat.',
            'jumlah_orang.min' => 'Jumlah orang minimal satu.',
            'jumlah_orang.max' => 'Jumlah orang maksimal 50.',
        ];
    }

    /** Nomor lokal 08... disimpan sebagai 62... */
    public function whatsappTernormalisasi(): string
    {
        $nomor = (string) $this->input('whatsapp');

        return str_starts_with($nomor, '08') ? '62'.substr($nomor, 1) : $nomor;
    }
}
