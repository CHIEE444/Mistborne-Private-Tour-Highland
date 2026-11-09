<?php

namespace App\Http\Requests\Admin\Perjalanan;

use App\Models\Kendaraan;
use App\Models\Perjalanan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Aturan & pesan validasi bersama form Journey (tambah & ubah). Semua isian wajib diisi;
 * satu-satunya pengecualian: gambar sampul saat ubah (sampul lama dipertahankan), dan
 * urutan tampil (otomatis 0).
 */
abstract class PerjalananRequest extends FormRequest
{
    /**
     * @return array<int, mixed>
     */
    abstract protected function aturanNama(): array;

    /**
     * @return array<int, mixed>
     */
    abstract protected function aturanSampul(): array;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jenis' => ['nullable', Rule::in([Perjalanan::JENIS_JOURNEY, Perjalanan::JENIS_PAKET])],
            'chip' => ['nullable', 'required_if:jenis,paket', 'string', 'min:2', 'max:60', 'not_regex:/[<>]/'],
            'label_unggulan' => ['nullable', 'string', 'max:120', 'not_regex:/[<>]/'],
            'rating_label' => ['nullable', 'regex:/^[0-5](\.\d{1,2})?$/'],
            'nama' => $this->aturanNama(),
            'region' => ['required', Rule::in(array_keys(Perjalanan::daftarRegion()))],
            'tempat' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
            'tagline' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
            'deskripsi' => ['required', 'string', 'max:5000', 'not_regex:/[<>]/'],
            'harga' => ['required', 'integer', 'min:1000', 'max:1000000000'],
            'durasi_hari' => ['required', 'integer', 'min:2', 'max:30'],
            'maks_orang' => ['required', 'integer', 'min:1', 'max:10'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'tampil_beranda' => ['nullable', 'boolean'],
            'rekomendasi_penginapan' => ['nullable', 'string', 'max:3000', 'not_regex:/[<>]/'],
            'kendaraan_id' => ['nullable', 'integer', Rule::exists('kendaraan', 'id')->whereNull('deleted_at')],
            'alasan_kendaraan' => ['nullable', 'required_with:kendaraan_id', 'string', 'max:300', 'not_regex:/[<>]/'],
            'img' => $this->aturanSampul(),
            'hapus_img' => ['nullable', 'boolean'],

            'highlights' => ['required', 'array', 'min:1'],
            'highlights.*' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],

            'itinerary' => ['required', 'array', 'min:1'],
            'itinerary.*.t' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
            'itinerary.*.x' => ['required', 'string', 'max:500', 'not_regex:/[<>]/'],

            'praktis' => ['required', 'array'],
            'praktis.pickup' => ['required', 'string', 'max:255'],
            'praktis.start' => ['required', 'string', 'max:255'],
            'praktis.capacity' => ['required', 'string', 'max:255'],
            'praktis.vehicle' => ['nullable', 'string', 'max:255'],
            'praktis.tips' => ['required', 'string', 'max:500'],
        ];
    }

    /** Itinerary tidak boleh menyebut hari melebihi durasi (mis. "Hari 4" pada journey 3 hari). */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $durasi = (int) $this->input('durasi_hari');

            $this->periksaMobilTetap($validator);

            foreach ((array) $this->input('itinerary', []) as $indeks => $baris) {
                if (is_array($baris) && preg_match('/^\s*Hari\s+(\d+)/iu', (string) ($baris['t'] ?? ''), $cocok) === 1 && $durasi > 0 && (int) $cocok[1] > $durasi) {
                    $validator->errors()->add("itinerary.{$indeks}.t", "Itinerary menyebut Hari {$cocok[1]}, padahal durasi journey hanya {$durasi} hari. Sesuaikan durasi atau itinerary.");
                }
            }
        });
    }

    /** Mobil tetap hanya untuk journey/paket baku, dan kursinya harus muat kapasitas maksimal ditambah guide yang merangkap driver. */
    private function periksaMobilTetap(Validator $validator): void
    {
        $kendaraanId = $this->integer('kendaraan_id');

        if (! $kendaraanId || $validator->errors()->hasAny(['kendaraan_id', 'maks_orang'])) {
            return;
        }

        $perjalanan = $this->route('perjalanan');

        if ($perjalanan instanceof Perjalanan && ! $perjalanan->semuaTermasuk()) {
            $validator->errors()->add('kendaraan_id', 'Journey kustom memakai kendaraan yang disesuaikan, jadi tidak punya mobil tetap.');

            return;
        }

        $kendaraan = Kendaraan::query()->find($kendaraanId);
        $butuh = (int) $this->input('maks_orang') + (int) config('mistborne.kursi_staf', 1);

        if ($kendaraan !== null && $kendaraan->kapasitas < $butuh) {
            $validator->errors()->add('kendaraan_id', "Mobil ini hanya punya {$kendaraan->kapasitas} kursi, kurang dari {$butuh} kursi ({$this->input('maks_orang')} tamu + guide yang merangkap driver). Pilih mobil yang lebih besar atau kurangi kapasitas maksimal.");
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama' => $this->input('jenis') === Perjalanan::JENIS_PAKET ? 'nama paket' : 'nama journey',
            'chip' => 'kategori',
            'label_unggulan' => 'label unggulan',
            'rating_label' => 'rating',
            'region' => 'region',
            'tempat' => 'lokasi destinasi',
            'tagline' => 'tagline',
            'deskripsi' => 'deskripsi',
            'harga' => 'harga',
            'durasi_hari' => 'durasi',
            'maks_orang' => 'kapasitas maksimal',
            'kendaraan_id' => 'mobil destinasi',
            'alasan_kendaraan' => 'alasan memilih mobil',
            'urutan' => 'urutan tampil',
            'img' => 'gambar sampul',
            'rekomendasi_penginapan' => 'rekomendasi penginapan',
            'highlights' => 'highlight',
            'highlights.*' => 'highlight',
            'itinerary' => 'itinerary',
            'itinerary.*.t' => 'hari itinerary',
            'itinerary.*.x' => 'kegiatan itinerary',
            'praktis.pickup' => 'titik jemput',
            'praktis.start' => 'jam mulai',
            'praktis.capacity' => 'keterangan kapasitas',
            'praktis.vehicle' => 'rekomendasi kendaraan',
            'praktis.tips' => 'tips',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'string' => ':Attribute harus berupa teks.',
            'integer' => ':Attribute harus berupa angka bulat.',
            'max.string' => ':Attribute maksimal :max karakter.',
            'max.numeric' => ':Attribute maksimal :max.',

            'chip.required_if' => 'Kategori paket wajib diisi, mis. Sunrise atau Kawah & Telaga.',
            'rating_label.regex' => 'Rating berupa angka 0 sampai 5, contoh 4.9 atau 4.95.',
            'nama.unique' => 'Nama journey ini sudah dipakai. Gunakan nama lain.',
            'region.required' => 'Pilih region destinasi.',
            'region.in' => 'Region yang dipilih tidak dikenal. Pilih dari daftar.',
            'harga.min' => 'Harga minimal Rp 1.000.',
            'harga.max' => 'Harga terlalu besar. Periksa kembali angkanya.',
            'durasi_hari.min' => 'Durasi journey minimal 2 hari 1 malam.',
            'not_regex' => ':Attribute tidak boleh memuat karakter < atau >.',
            'durasi_hari.max' => 'Durasi maksimal 30 hari.',
            'maks_orang.min' => 'Kapasitas minimal 1 orang.',
            'maks_orang.max' => 'Kapasitas maksimal 10 tamu (di luar guide yang merangkap driver).',
            'urutan.min' => 'Urutan tampil tidak boleh negatif.',
            'kendaraan_id.exists' => 'Mobil yang dipilih tidak ditemukan. Pilih dari daftar.',
            'alasan_kendaraan.required_with' => 'Isi alasan memilih mobil ini (mis. kuat di tanjakan), supaya tamu tahu kenapa mobilnya itu.',
            'img.required' => 'Gambar sampul wajib diunggah.',
            'img.image' => 'Berkas sampul harus berupa gambar (JPG, PNG, atau WebP).',
            'img.mimes' => 'Gambar sampul harus berformat JPG, PNG, atau WebP.',
            'img.max' => 'Ukuran gambar sampul maksimal 4 MB.',
            'img.uploaded' => 'Gambar sampul gagal diunggah. Pastikan ukurannya maksimal 4 MB lalu coba lagi.',
            'highlights.required' => 'Isi minimal satu highlight.',
            'highlights.min' => 'Isi minimal satu highlight.',
            'itinerary.required' => 'Isi minimal satu baris itinerary.',
            'itinerary.min' => 'Isi minimal satu baris itinerary.',
            'itinerary.*.t.required' => 'Waktu itinerary wajib diisi, mis. "Hari 1 · 03.30".',
            'itinerary.*.x.required' => 'Kegiatan itinerary wajib diisi.',
            'highlights.*.required' => 'Highlight tidak boleh kosong. Isi atau hapus barisnya.',
            'praktis.required' => 'Info praktis wajib diisi lengkap.',
            'praktis.pickup.required' => 'Titik jemput wajib diisi.',
            'praktis.start.required' => 'Jam mulai wajib diisi.',
            'praktis.capacity.required' => 'Keterangan kapasitas wajib diisi, mis. "Maks. 4 tamu per booking, di luar pemandu yang merangkap sopir".',
            'praktis.tips.required' => 'Tips wajib diisi.',
        ];
    }
}
