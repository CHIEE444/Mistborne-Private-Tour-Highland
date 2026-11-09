<?php

namespace App\Http\Requests\Admin\Pesanan;

use App\Bantuan\AturanNama;
use App\Bantuan\AturanPenginapan;
use App\Bantuan\JamPenjemputan;
use App\Bantuan\NomorWhatsApp;
use App\Bantuan\PilihanKendaraan;
use App\Layanan\KapasitasTripLayanan;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SimpanPesananRequest extends FormRequest
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
        return $this->user()?->can('create', Pesanan::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'perjalanan_id' => ['required', Rule::exists('perjalanan', 'id')->where('template_pesanan_kustom', false)->whereNull('deleted_at')],
            'tanggal_trip' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.now()->addDays(7)->toDateString(),
                'before_or_equal:'.now()->addDays(7)->addYear()->toDateString(),
            ],
            'nama_lengkap' => AturanNama::aturan(),
            'whatsapp' => ['required', 'string', 'regex:/^08[0-9]{7,12}$/'],
            ...AturanPenginapan::aturan(izinkanDiurus: true, izinkanDipesankan: true),
            ...PilihanKendaraan::aturan(),
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

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'perjalanan_id.required' => 'Journey wajib dipilih.',
            'perjalanan_id.exists' => 'Journey yang dipilih tidak ditemukan. Silakan pilih ulang.',
            'tanggal_trip.required' => 'Tanggal trip wajib dipilih.',
            'tanggal_trip.date_format' => 'Format tanggal trip tidak valid. Silakan pilih tanggal dari daftar.',
            'tanggal_trip.after_or_equal' => 'Tanggal trip harus minimal tujuh hari dari hari ini (H-7).',
            'tanggal_trip.before_or_equal' => 'Tanggal trip hanya dapat dipilih sampai satu tahun ke depan.',
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
            $hargaEfektif = $harga !== null && $harga !== ''
                ? (int) $harga
                : Perjalanan::query()->find($this->input('perjalanan_id'))?->hargaDefault($this->input('status_penginapan'), (int) $this->input('jumlah_wisatawan'));

            if ($dibayar !== null && $dibayar !== '' && (int) $dibayar > 0 && ($hargaEfektif === null || (int) $dibayar > (int) $hargaEfektif)) {
                $validator->errors()->add('nominal_dibayar', $hargaEfektif === null
                    ? 'Tentukan harga perjalanan sebelum mencatat pembayaran.'
                    : 'Jumlah yang sudah dibayar tidak boleh melebihi total harga pesanan.');
            }

            if ($this->input('status_penginapan') === 'diurus' && ! Perjalanan::query()->find($this->input('perjalanan_id'))?->menawarkanAllIn()) {
                $validator->errors()->add('status_penginapan', 'All-inclusive hanya untuk journey baku, tidak untuk journey kustom.');
            }

            $kapasitas = Perjalanan::query()->whereKey($this->input('perjalanan_id'))->value('maks_orang');
            if (PilihanKendaraan::maksTamu($this->input('kendaraan_pilihan')) === null && $kapasitas !== null && (int) $this->input('jumlah_wisatawan') > $kapasitas) {
                $validator->errors()->add('jumlah_wisatawan', "Jumlah wisatawan melebihi kapasitas journey ini (maksimal {$kapasitas} orang).");
            }

            if ($validator->errors()->has('perjalanan_id') || $validator->errors()->has('tanggal_trip')) {
                return;
            }

            // Sama dengan semua formulir: tanggal harus Tanggal Tersedia journey ini, kuota belum penuh, dan masih ada guide bebas.
            $perjalanan = Perjalanan::query()->find($this->input('perjalanan_id'));
            if ($perjalanan) {
                $slot = app(KapasitasTripLayanan::class)->cek($perjalanan, (string) $this->input('tanggal_trip'));
                if (! $slot['tersedia']) {
                    $validator->errors()->add('tanggal_trip', $slot['alasan']);
                }
            }
        });
    }

    public function whatsappTernormalisasi(): string
    {
        return '62'.substr((string) $this->input('whatsapp'), 1);
    }
}
