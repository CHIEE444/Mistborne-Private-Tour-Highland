{{-- HALAMAN DETAIL ARTIKEL (/artikel/{slug}) — badan artikel dari editor admin (Trix) tampil
     lewat Artikel::isiAman() (sudah dibersihkan), diberi gaya di css/base.css (.isi-artikel). --}}
<x-tata-letak.aplikasi
    :title="$data['title'].' — MISTBORNE'"
    :description="\Illuminate\Support\Str::limit($data['excerpt'] ?? '', 155)"
    :image="$data['img'] ? url($data['img']) : null"
>
    <article class="mx-auto max-w-[760px] px-4 pb-24 pt-28 sm:px-8 md:pt-32" aria-labelledby="artikel-nama">
        <x-bersama.tombol-beranda class="mb-8" />

        <p class="text-sm font-medium text-[#1F4D36]">{{ $data['category'] }}</p>
        <h1 id="artikel-nama" class="mt-2 font-serif text-3xl font-semibold leading-tight tracking-tight text-[#14272B] md:text-[42px]">{{ $data['title'] }}</h1>
        <p class="mt-4 text-lg leading-relaxed text-[#55605C]">{{ $data['excerpt'] }}</p>
        <p class="mt-5 text-sm text-[#55605C]">
            <span class="font-medium text-[#14272B]">{{ $data['author'] }}</span>
            <span aria-hidden="true"> · </span><span>{{ $data['date'] }}</span>
            <span aria-hidden="true"> · </span><span>{{ $data['read'] }}</span>
        </p>

        @if ($data['img'])
            <img src="{{ $data['img'] }}" alt="{{ $data['alt'] }}" width="1200" height="630" decoding="async" class="mt-8 aspect-[1200/630] w-full rounded-[24px] object-cover">
        @endif

        <div class="isi-artikel mt-10">{!! $artikel->isiAman() !!}</div>
    </article>

    @if ($lainnya->isNotEmpty())
        <section class="mx-auto max-w-[1100px] border-t border-[#E3E5E1] px-4 pb-20 pt-12 sm:px-8" aria-labelledby="lainnya-judul">
            <h2 id="lainnya-judul" class="font-serif text-2xl font-medium text-[#12301F]">{{ __('Artikel Lainnya') }}</h2>
            <div class="mt-6 grid gap-8 sm:grid-cols-3">
                @foreach ($lainnya as $item)
                    <a href="{{ $item['url'] }}" class="group block">
                        @if ($item['img'])
                            <img src="{{ $item['img'] }}" alt="{{ $item['alt'] }}" width="600" height="315" loading="lazy" decoding="async" class="aspect-[1200/630] w-full rounded-[16px] object-cover">
                        @endif
                        <p class="mt-3 text-xs font-medium text-[#1F4D36]">{{ $item['category'] }}</p>
                        <h3 class="mt-1 text-base font-semibold leading-snug text-[#14272B] group-hover:text-[#1F4D36]">{{ $item['title'] }}</h3>
                        <p class="mt-1 text-sm text-[#55605C]">{{ $item['date'] }} · {{ $item['read'] }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-tata-letak.aplikasi>
