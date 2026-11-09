<?php

namespace App\Http\Requests\Admin\Pesanan;

use App\Bantuan\AturanNama;
use App\Bantuan\AturanPenginapan;
use App\Bantuan\JamPenjemputan;
use App\Bantuan\NomorWhatsApp;
use App\Bantuan\PilihanKendaraan;
use App\Enums\BookingStatus;
use App\Models\Pesanan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PerbaruiPesananRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'whatsapp' => NomorWhatsApp::lokal($this->input('whatsapp')),
            'nama_lengkap' => AturanNama::rapikan($this->input('nama_lengkap')),
            'waktu_pickup' => JamPenjemputan::normalisasi($this->input('waktu_pickup')),
        ]);
    }

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
            'nama_lengkap' => AturanNama::aturan(),
            'whatsapp' => ['required', 'string', 'regex:/^08[0-9]{7,12}$/'],
            ...AturanPenginapan::aturan(izinkanDiurus: true, izinkanDipesankan: true),
            ...PilihanKendaraan::aturan(nilaiLama: $this->route('pesanan') instanceof Pesanan ? $this->route('pesanan')->kendaraan_pilihan : null),
            'lokasi_pickup' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
            'waktu_pickup' => ['nullable', 'date_format:H:i'],
            'paket_opsional' => ['nullable', 'string', 'max:255'],
            'jumlah_wisatawan' => ['required', 'integer', 'min:1', 'max:50'],
            'permintaan_khusus' => ['nullable', 'string', 'max:1000'],
            'harga' => ['nullable', 'integer', 'min:1'],
            'nominal_dibayar' => ['nullable', 'integer', 'min:0'],
            'metode_pembayaran' => ['nullable', Rule::in(['transfer', 'qris', 'tunai', 'lainnya'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_lengkap.required' => 'Nama lengkap tamu wajib diisi.',
            'nama_lengkap.max' => 'Nama lengkap maksimal 255 karakter.',
            'nama_lengkap.not_regex' => 'Nama tidak boleh memuat karakter < atau >.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex' => 'Masukkan nomor WhatsApp dengan angka saja, diawali 08 atau +62, contoh 081234567890.',
            ...AturanNama::pesan(),
            ...AturanPenginapan::pesan(),
            ...PilihanKendaraan::pesan(),
            'lokasi_pickup.required' => 'Alamat atau titik jemput wajib diisi.',
            'lokasi_pickup.max' => 'Alamat atau titik jemput maksimal 255 karakter.',
            'lokasi_pickup.not_regex' => 'Lokasi jemput tidak boleh memuat karakter < atau >.',
            'waktu_pickup.date_format' => 'Format jam jemput tidak valid.',
            'jumlah_wisatawan.required' => 'Jumlah wisatawan wajib diisi.',
            'jumlah_wisatawan.integer' => 'Jumlah wisatawan harus berupa angka bulat, misalnya 4.',
            'jumlah_wisatawan.min' => 'Jumlah wisatawan minimal 1 orang.',
            'jumlah_wisatawan.max' => 'Jumlah wisatawan maksimal 50 orang.',
            'permintaan_khusus.max' => 'Permintaan khusus maksimal 1.000 karakter.',
            'harga.integer' => 'Harga harus berupa angka bulat.',
            'harga.min' => 'Harga minimal Rp 1. Kosongkan kolom untuk memakai harga journey.',
            'nominal_dibayar.integer' => 'Nominal pembayaran harus berupa angka bulat.',
            'nominal_dibayar.min' => 'Nominal pembayaran tidak boleh kurang dari nol.',
            'metode_pembayaran.in' => 'Pilih metode pembayaran yang tersedia.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $maksUnit = PilihanKendaraan::maksTamu($this->input('kendaraan_pilihan'));
            if ($maksUnit !== null && (int) $this->input('jumlah_wisatawan') > $maksUnit) {
                $validator->errors()->add('jumlah_wisatawan', "Mobil destinasi hanya memuat {$maksUnit} tamu (guide yang merangkap driver memakai 1 kursi). Kurangi jumlah tamu atau ganti kendaraan.");
            }

            $harga = $this->input('harga');
            $dibayar = $this->input('nominal_dibayar');
            $pesanan = $this->route('pesanan');
            $biayaGantiJadwal = $pesanan instanceof Pesanan ? $pesanan->totalBiayaGantiJadwal() : 0;
            $hargaEfektif = $harga !== null && $harga !== ''
                ? (int) $harga
                : ($pesanan instanceof Pesanan ? $pesanan->perjalanan?->hargaDefault($this->input('status_penginapan'), (int) $this->input('jumlah_wisatawan')) : null);
            $totalTagihan = $hargaEfektif !== null ? (int) $hargaEfektif + $biayaGantiJadwal : null;

            if ($dibayar !== null && $dibayar !== '' && (int) $dibayar > 0 && ($totalTagihan === null || (int) $dibayar > $totalTagihan)) {
                $validator->errors()->add('nominal_dibayar', $totalTagihan === null
                    ? 'Tentukan harga perjalanan sebelum mencatat pembayaran.'
                    : 'Jumlah yang sudah dibayar tidak boleh melebihi total tagihan termasuk biaya ganti jadwal.');
            }

            if ($pesanan instanceof Pesanan && $this->input('status_penginapan') === 'diurus'
                && $pesanan->status_penginapan?->value !== 'diurus' && ! $pesanan->perjalanan?->menawarkanAllIn()) {
                $validator->errors()->add('status_penginapan', 'All-inclusive hanya untuk journey baku, tidak untuk journey kustom.');
            }

            $kapasitas = $pesanan instanceof Pesanan ? $pesanan->perjalanan()->value('maks_orang') : null;
            if (PilihanKendaraan::maksTamu($this->input('kendaraan_pilihan')) === null && $kapasitas !== null && (int) $this->input('jumlah_wisatawan') > $kapasitas) {
                $validator->errors()->add('jumlah_wisatawan', "Jumlah wisatawan melebihi kapasitas journey ini (maksimal {$kapasitas} orang).");
            }

            if ($pesanan instanceof Pesanan && $pesanan->status === BookingStatus::Payment) {
                if ($hargaEfektif === null || (int) ($dibayar ?: 0) < (int) ceil((int) $hargaEfektif * 0.3)) {
                    $validator->errors()->add('nominal_dibayar', 'Pesanan yang sudah bayar wajib mencatat total harga dan uang muka minimal 30%.');
                }
            }
        });
    }

    public function whatsappTernormalisasi(): string
    {
        return '62'.substr((string) $this->input('whatsapp'), 1);
    }
}
