@props([
    'status' => null,
    'nama' => null,
    'alamat' => null,
    'statusField' => 'status_penginapan',
    'namaField' => 'nama_hotel',
    // All-inclusive (status "diurus") hanya untuk pesanan journey biasa; form pesanan kustom mematikannya.
    'allIn' => true,
    // Awalan id kolom, untuk halaman yang memuat formulir penginapan lebih dari sekali (mis. detail pesanan kustom + modal).
    'awalanId' => '',
])

@php
    // Sistem baku all-inclusive: pesanan baru dari admin memilih "Diurus MISTBORNE" kecuali dipilih lain.
    $nilaiStatus = old($statusField, $status instanceof \BackedEnum ? $status->value : ($status ?? ($allIn ? 'diurus' : null)));
@endphp

{{-- Penginapan tamu: status wajib; nama hotel wajib hanya bila "sudah punya" (aturan: App\Bantuan\AturanPenginapan). --}}
<div {{ $attributes->class(['grid gap-4 sm:grid-cols-2']) }} x-data="{ penginapan: @js((string) $nilaiStatus) }">
    <x-admin.formulir.pilihan
        :name="$statusField"
        :id-input="$awalanId.$statusField"
        label="Penginapan Tamu"
        wajib
        :pilihan="['sudah' => 'Sudah punya penginapan', 'belum' => 'Belum punya, butuh rekomendasi', ...($allIn ? ['dipesankan' => 'Dipesankan MISTBORNE (bayar ke hotel)', 'diurus' => 'Diurus MISTBORNE (All-inclusive)'] : [])]"
        :value="$nilaiStatus"
        placeholder="Pilih status penginapan"
        x-model="penginapan"
    />

    <div x-show="penginapan === 'sudah' || penginapan === 'diurus' || penginapan === 'dipesankan'" x-cloak>
        <x-admin.formulir.masukan
            :name="$namaField"
            :id-input="$awalanId.$namaField"
            label="Nama Hotel / Penginapan"
            :value="$nama"
            x-bind:placeholder="penginapan === 'diurus' || penginapan === 'dipesankan' ? 'Terisi otomatis dari kartu Reservasi Hotel (boleh dikosongkan)' : 'Nama hotel atau penginapan tamu. Contoh: Hotel Tugu Malang'"
            x-bind:required="penginapan === 'sudah'"
            x-bind:disabled="penginapan !== 'sudah' && penginapan !== 'diurus' && penginapan !== 'dipesankan'"
        />
    </div>

    <div class="sm:col-span-2" x-show="penginapan === 'sudah'" x-cloak>
        <x-admin.formulir.masukan
            name="alamat_hotel"
            :id-input="$awalanId.'alamat_hotel'"
            label="Alamat Hotel / Penginapan"
            :value="$alamat"
            placeholder="Alamat lengkap hotel tamu, dipakai sebagai titik jemput dan antar. Contoh: Jl. Malioboro No. 60, Yogyakarta"
            x-bind:disabled="penginapan !== 'sudah'"
        />
    </div>

    <p x-show="penginapan === 'dipesankan'" x-cloak class="self-end rounded-lg bg-status-completed-bg px-3.5 py-2.5 text-xs leading-5 text-status-completed-teks">
        MISTBORNE memesankan hotel; tamu membayar kamar langsung ke hotel. Kelola reservasinya di kartu &ldquo;Reservasi Hotel&rdquo; pada detail pesanan.
    </p>

    <p x-show="penginapan === 'diurus'" x-cloak class="self-end rounded-lg bg-status-completed-bg px-3.5 py-2.5 text-xs leading-5 text-status-completed-teks">
        All-inclusive: pesankan hotel bintang 4 untuk tamu (4 orang per kamar) dan siapkan tiket masuk. Kelola reservasinya di kartu &ldquo;Reservasi Hotel&rdquo; pada detail pesanan. Harga sudah all-inclusive, tidak ada tambahan per orang.
    </p>

    <p x-show="penginapan === 'belum'" x-cloak class="self-end rounded-lg bg-status-payment-bg px-3.5 py-2.5 text-xs leading-5 text-status-payment-teks">
        Tamu belum punya penginapan. Kirim rekomendasi lewat tombol &ldquo;Rekomendasi Penginapan&rdquo; di detail pesanan, lalu pilih hotel di kartu &ldquo;Reservasi Hotel&rdquo; setelah tamu memberi kabar.
    </p>
</div>
