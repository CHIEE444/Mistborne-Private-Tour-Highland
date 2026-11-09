@php
    // KONSTANTA DESAIN komponen ini — ukuran, posisi, warna dari config/desain.php
    // (halaman pusat token). Panggil lewat warna()/ukuran()/font() — jangan hex baru di markup.
    $hijauTua = warna('hijauTua');
@endphp
{{-- Item bento galeri: foto dengan zoom halus saat hover; img text-transparent agar alt tidak tampil bila gambar gagal dimuat. --}}
@props(['item', 'index'])
@php
    $spanClasses = [
        0 => 'sm:col-span-2 sm:row-span-2',
        2 => 'sm:row-span-2',
        4 => 'sm:col-span-2',
        7 => 'sm:col-span-2 md:col-span-4',
    ];
    $isBig = ($index ?? 0) === 0;
@endphp
<div {{ $attributes }} class="group relative overflow-hidden rounded-[20px] border border-[#C2C8C1]/35 tier-1-shadow {{ $spanClasses[$index ?? 1] ?? '' }}">
    <div class="absolute inset-0 bg-gradient-to-br from-[#12301F] via-[#1B422B] to-[#0C1A12]"></div>
    <img alt="{{ $item['alt'] }}" loading="lazy" decoding="async" width="1200" height="900" class="text-transparent relative h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]" src="{{ $item['img'] }}" />
        {{-- Subjudul putih di bawah tiap kartu (tanpa aksen emas/badge) --}}
        <div class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-[#12301F]/70 via-[#12301F]/20 to-transparent p-4">
            <span class="{{ $isBig ? 'font-serif text-2xl font-semibold tracking-tight text-white' : 'truncate text-sm font-semibold text-white' }}">{{ $item['title'] }}</span>
        </div>
</div>
