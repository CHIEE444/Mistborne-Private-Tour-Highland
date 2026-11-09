@php
    $jam = now()->hour;
    $sapaan = match (true) {
        $jam < 11 => 'Selamat pagi',
        $jam < 15 => 'Selamat siang',
        $jam < 18 => 'Selamat sore',
        default => 'Selamat malam',
    };
    $namaDepan = \Illuminate\Support\Str::of(auth()->user()?->name ?? '')->trim()->before(' ')->value();
@endphp

{{-- Banner sambutan dashboard: foto dataran tinggi + sapaan sesuai jam. --}}
<section
    {{ $attributes->class(['relative isolate overflow-hidden rounded-[28px] shadow-admin-kartu']) }}
    aria-label="Sambutan"
    x-data="{
        tanggal: @js(now()->translatedFormat('l, d F Y')),
        sapaan: @js($sapaan),
        zona: @js(config('app.timezone')),
        perbarui() {
            const sekarang = new Date();
            this.tanggal = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric', timeZone: this.zona }).format(sekarang);
            const jam = Number(new Intl.DateTimeFormat('en-GB', { hour: '2-digit', hourCycle: 'h23', timeZone: this.zona }).format(sekarang));
            this.sapaan = jam < 11 ? 'Selamat pagi' : jam < 15 ? 'Selamat siang' : jam < 18 ? 'Selamat sore' : 'Selamat malam';
        },
    }"
    x-init="setInterval(() => perbarui(), 30000)"
>
    <img src="{{ asset('images/hero-2.jpg') }}" alt="" width="1920" height="1071" decoding="async" class="absolute inset-0 -z-20 size-full object-cover object-[center_60%]" />
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-[#46634F]/95 via-[#587A63]/80 to-[#6F8F79]/40" aria-hidden="true"></div>

    <div class="max-w-xl px-6 py-10 sm:px-8 sm:py-14">
        <p
            class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/15 px-3.5 py-1.5 text-xs font-semibold text-white backdrop-blur-sm sm:text-sm">
            <span class="material-symbols-outlined text-base" aria-hidden="true">calendar_month</span>
            <span x-text="tanggal">{{ now()->translatedFormat('l, d F Y') }}</span>
        </p>
        <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
            <span x-text="sapaan">{{ $sapaan }}</span>, {{ $namaDepan }}! <span class="inline-block origin-[70%_70%] motion-safe:animate-[wave_2.4s_ease-in-out_1]" aria-hidden="true">👋</span>
        </h2>
        <p class="mt-2 text-sm leading-relaxed text-white/85 sm:text-base">Berikut yang sedang terjadi di operasional ekspedisi dataran tinggi hari ini.</p>
    </div>
</section>
