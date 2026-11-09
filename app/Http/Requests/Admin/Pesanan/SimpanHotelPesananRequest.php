<?php

namespace App\Http\Requests\Admin\Pesanan;

use App\Enums\StatusReservasiHotel;
use App\Layanan\PemeriksaReservasiHotel;
use App\Models\Hotel;
use App\Models\Pesanan;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reservasi hotel untuk pesanan. Selain format isian, aturan bisnis (kamar cukup, tanggal sesuai trip,
 * hotel aktif, tipe kamar milik hotel) diperiksa lewat PemeriksaReservasiHotel yang juga dipakai panel "Periksa sebelum kirim".
 */
class SimpanHotelPesananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('pesanan')) ?? false;
    }

    /**
     * Formulir tahap "Pesan kamar" tidak lagi mengirim status dan kode booking: status ditentukan controller dan kode
     * booking diisi di tahap "Hotel setuju". Keduanya hanya diproses bila memang dikirim, jadi kode lama tidak terhapus.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tipe_kamar' => trim((string) $this->input('tipe_kamar')) ?: null,
            'catatan_reservasi_hotel' => trim((string) $this->input('catatan_reservasi_hotel')) ?: null,
            'setuju_biaya_ganti_hotel' => $this->boolean('setuju_biaya_ganti_hotel'),
            ...($this->has('kode_booking_hotel') ? ['kode_booking_hotel' => trim((string) $this->input('kode_booking_hotel')) ?: null] : []),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'hotel_id' => ['required', 'integer', Rule::exists('hotel', 'id')->whereNull('deleted_at')],
            'hotel_check_in' => ['required', 'date_format:Y-m-d'],
            'hotel_check_out' => ['required', 'date_format:Y-m-d', 'after:hotel_check_in'],
            'jumlah_kamar' => ['required', 'integer', 'min:1', 'max:30'],
            'tipe_kamar' => ['nullable', 'string', 'max:100', 'not_regex:/[<>]/'],
            'status_reservasi_hotel' => ['sometimes', Rule::enum(StatusReservasiHotel::class)],
            'kode_booking_hotel' => ['nullable', 'string', 'max:100', 'not_regex:/[<>]/', Rule::requiredIf(fn (): bool => $this->input('status_reservasi_hotel') === StatusReservasiHotel::Terkonfirmasi->value)],
            'catatan_reservasi_hotel' => ['nullable', 'string', 'max:500', 'not_regex:/[<>]/'],
            'setuju_biaya_ganti_hotel' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'hotel_id' => 'hotel',
            'hotel_check_in' => 'tanggal check-in',
            'hotel_check_out' => 'tanggal check-out',
            'jumlah_kamar' => 'jumlah kamar',
            'tipe_kamar' => 'tipe kamar',
            'status_reservasi_hotel' => 'status reservasi',
            'kode_booking_hotel' => 'kode booking hotel',
            'catatan_reservasi_hotel' => 'catatan reservasi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'hotel_id.exists' => 'Hotel yang dipilih tidak ditemukan. Pilih dari daftar.',
            'hotel_check_in.date_format' => 'Tanggal check-in tidak valid.',
            'hotel_check_out.date_format' => 'Tanggal check-out tidak valid.',
            'hotel_check_out.after' => 'Tanggal check-out harus setelah check-in.',
            'jumlah_kamar.integer' => 'Jumlah kamar harus berupa angka bulat.',
            'jumlah_kamar.min' => 'Jumlah kamar minimal 1.',
            'jumlah_kamar.max' => 'Jumlah kamar maksimal 30.',
            'max.string' => ':Attribute maksimal :max karakter.',
            'not_regex' => ':Attribute tidak boleh memuat karakter < atau >.',
            'status_reservasi_hotel.Illuminate\\Validation\\Rules\\Enum' => 'Pilih status reservasi dari daftar.',
            'kode_booking_hotel.required' => 'Isi kode booking atau nama petugas hotel yang mengonfirmasi sebelum menandai terkonfirmasi.',
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Pesanan $pesanan */
            $pesanan = $this->route('pesanan');
            $hotel = Hotel::withTrashed()->find($this->integer('hotel_id'));

            $hasil = app(PemeriksaReservasiHotel::class)->periksa(
                $pesanan,
                $hotel,
                Carbon::createFromFormat('Y-m-d', $this->input('hotel_check_in'))->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $this->input('hotel_check_out'))->startOfDay(),
                $this->integer('jumlah_kamar'),
                $this->input('tipe_kamar'),
            );

            $hotelLamaSama = $hotel !== null && (int) $pesanan->hotel_id === $hotel->id;

            // Hotel yang sudah terkonfirmasi tidak bisa diganti begitu saja: penggantian dikenai biaya (charge).
            $hotelBerganti = $pesanan->hotel_id !== null && $hotel !== null && ! $hotelLamaSama;
            if ($hotelBerganti && $pesanan->status_reservasi_hotel === StatusReservasiHotel::Terkonfirmasi && ! $this->boolean('setuju_biaya_ganti_hotel')) {
                $validator->errors()->add('hotel_id', 'Hotel sudah terkonfirmasi dan tidak bisa diganti begitu saja. Mengganti hotel dikenai biaya (charge); centang persetujuan biaya ganti hotel bila tamu setuju.');
            }

            foreach (app(PemeriksaReservasiHotel::class)->kegagalanKeras($hasil) as $item) {
                // Hotel yang sudah terpilih sebelumnya tetap boleh disimpan ulang walau kini nonaktif.
                if ($item['kunci'] === 'hotel' && $hotelLamaSama && $hotel?->aktif === false) {
                    continue;
                }

                $validator->errors()->add($item['field'], $item['keterangan']);
            }
        }];
    }
}
