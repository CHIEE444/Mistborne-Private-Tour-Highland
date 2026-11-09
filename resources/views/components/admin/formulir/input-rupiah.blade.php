@props([
    'name',
    'id' => null,
    'value' => null,
    'nullable' => false,
    'invalid' => false,
    // Ekspresi Alpine untuk menonaktifkan isian; dipasang di kedua input agar nilai tersembunyi ikut tidak terkirim.
    'nonaktifJika' => null,
])

{{--
    Isian nominal Rupiah tanpa label: tampil berpemisah ribuan (1.500.000), huruf dan simbol langsung terbuang saat
    diketik, dan yang terkirim ke server tetap angka bulat lewat input tersembunyi. Dipakai <x-admin.formulir.uang>
    dan form yang butuh id sendiri (mis. beberapa form transfer hotel dalam satu halaman).
--}}
@php
    $nilaiAwal = $nullable && ($value === null || $value === '') ? null : (int) ($value ?? 0);
@endphp

<div
    x-data="{
        nilai: @js($nilaiAwal),
        bolehKosong: @js($nullable),
        formatRupiah(angka) { return new Intl.NumberFormat('id-ID').format(angka || 0); },
        ubah(event) {
            const kolom = event.target;
            const mentah = kolom.value.replace(/[^0-9]/g, '');
            const angkaSebelumKursor = kolom.value.slice(0, kolom.selectionStart ?? kolom.value.length).replace(/[^0-9]/g, '').length;
            this.nilai = this.bolehKosong && mentah === '' ? null : (parseInt(mentah) || 0);

            // Huruf dan simbol langsung dibuang dari tampilan; x-bind:value tidak menggambar ulang bila nilainya tidak berubah.
            kolom.value = this.nilai === null ? '' : this.formatRupiah(this.nilai);
            let posisi = 0;
            for (let angka = 0; posisi < kolom.value.length && angka < angkaSebelumKursor; posisi++) {
                if (/[0-9]/.test(kolom.value[posisi])) angka++;
            }
            kolom.setSelectionRange(posisi, posisi);
        },
    }"
    class="relative"
>
    <span class="pointer-events-none absolute start-3.5 top-1/2 -translate-y-1/2 text-sm text-muted">Rp</span>

    <input type="hidden" name="{{ $name }}" :value="nilai ?? ''" @if ($nonaktifJika) x-bind:disabled="{{ $nonaktifJika }}" @endif />

    <input
        type="text"
        inputmode="numeric"
        autocomplete="off"
        id="{{ $id ?? $name }}"
        x-bind:value="nilai === null ? '' : formatRupiah(nilai)"
        x-on:input="ubah($event)"
        @if ($nonaktifJika) x-bind:disabled="{{ $nonaktifJika }}" @endif
        {{ $attributes->class([
            'w-full rounded-lg border bg-white py-2.5 ps-10 pe-3.5 text-sm text-ink placeholder:text-muted focus:outline-none focus:ring-1 disabled:bg-page disabled:text-muted',
            'border-status-dibatalkan-teks focus:border-status-dibatalkan-teks focus:ring-status-dibatalkan-teks' => $invalid,
            'border-surface-border focus:border-hijau-aksi focus:ring-hijau-aksi' => ! $invalid,
        ]) }}
    />
</div>
