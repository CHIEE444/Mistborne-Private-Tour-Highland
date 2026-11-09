@php
    $hijauTua = warna('hijauTua');
    $emas = warna('emas');
    $abuTeks = warna('abuTeks');
    $abuBorder = warna('abuBorder');
@endphp
<x-tata-letak.aplikasi title="Beri Rating & Ulasan">
    <section class="mx-auto max-w-[620px] px-4 pb-20 pt-28 sm:px-8 md:pt-32" aria-labelledby="rating-judul">
        <div class="mx-auto mb-10 max-w-[520px] text-center">
            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em]" style="color: {{ $abuTeks }}">
                Terima Kasih Sudah Berkunjung
            </span>
            <h1 class="font-serif text-3xl font-medium tracking-tight leading-tight md:text-4xl" id="rating-judul" style="color: {{ $hijauTua }}">
                Bagikan Pengalaman Anda
            </h1>
        </div>

        @if (! $pesanan)
            <div class="rounded-[28px] border bg-white p-8 text-center" style="border-color: {{ $abuBorder }}">
                <p class="text-base" style="color: {{ $abuTeks }}">Tautan ulasan tidak valid. Mohon periksa kembali tautan yang Anda terima.</p>
            </div>
        @elseif (session('sukses'))
            <div class="rounded-[28px] border bg-white p-8 text-center" style="border-color: {{ $abuBorder }}">
                <span class="material-symbols-outlined mb-3 text-4xl" style="color: {{ $emas }}" aria-hidden="true">favorite</span>
                <p class="text-lg font-medium" style="color: {{ $hijauTua }}">Terima kasih atas ulasan Anda!</p>
                <p class="mt-2 text-sm" style="color: {{ $abuTeks }}">Tim kami akan meninjau ulasan Anda sebelum ditampilkan di situs.</p>
            </div>
        @elseif (! $tautanAktif)
            <div class="rounded-[28px] border bg-white p-8 text-center" style="border-color: {{ $abuBorder }}">
                <p class="text-base" style="color: {{ $abuTeks }}">
                    @if ($pesanan->ulasan()->exists())
                        Anda sudah mengirimkan ulasan untuk pesanan ini. Terima kasih!
                    @else
                        Tautan ulasan untuk pesanan {{ $pesanan->kode }} sudah tidak berlaku lagi.
                    @endif
                </p>
            </div>
        @else
            <form method="POST" action="{{ route('rating.simpan', $pesanan->token_rating) }}" class="rounded-[28px] border bg-white p-6 shadow-sm sm:p-8" style="border-color: {{ $abuBorder }}" x-data="{ rating: {{ old('rating', 5) }} }">
                @csrf

                <p class="mb-1 text-sm font-medium" style="color: {{ $hijauTua }}">{{ $pesanan->nama_perjalanan }}</p>
                <p class="mb-6 text-xs" style="color: {{ $abuTeks }}">{{ $pesanan->kode }} &middot; {{ $pesanan->tanggal_trip->translatedFormat('d F Y') }}</p>

                <div class="mb-6">
                    <label class="mb-2 block text-sm font-medium" style="color: {{ $hijauTua }}">Rating</label>
                    <div class="flex items-center gap-1">
                        @for ($i = 1; $i <= 5; $i++)
                            <button
                                type="button"
                                x-on:click="rating = {{ $i }}"
                                class="transition-transform hover:scale-110"
                                aria-label="{{ $i }} dari 5 bintang"
                            >
                                <span
                                    class="material-symbols-outlined text-3xl"
                                    :style="rating >= {{ $i }} ? 'color: {{ $emas }}; font-variation-settings: \'FILL\' 1' : 'color: {{ $abuBorder }}'"
                                    aria-hidden="true"
                                >star</span>
                            </button>
                        @endfor
                        <input type="hidden" name="rating" x-bind:value="rating" />
                    </div>
                    @error('rating') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mb-6">
                    <label for="rating-komentar" class="mb-1.5 block text-sm font-medium" style="color: {{ $hijauTua }}">Komentar</label>
                    <textarea
                        name="komentar" id="rating-komentar" rows="4" placeholder="Ceritakan pengalaman perjalanan Anda..."
                        class="w-full rounded-xl border px-4 py-2.5 text-sm focus:outline-none focus:ring-1"
                        style="border-color: {{ $abuBorder }}"
                    >{{ old('komentar') }}</textarea>
                    @error('komentar') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="mb-6 flex items-start gap-2 text-sm" style="color: {{ $abuTeks }}">
                    <input type="checkbox" name="izin_tampil" value="1" class="mt-0.5" @checked(old('izin_tampil')) />
                    Saya mengizinkan nama dan ulasan saya ditampilkan di situs MISTBORNE.
                </label>

                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-full px-8 py-3.5 text-sm font-bold uppercase tracking-wide text-white transition-colors"
                    style="background-color: {{ $hijauTua }}"
                >
                    Kirim Ulasan
                </button>
            </form>
        @endif
    </section>
</x-tata-letak.aplikasi>
