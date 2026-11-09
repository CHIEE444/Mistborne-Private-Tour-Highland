@props(['hotel', 'ringkas' => false])

{{-- Kartu hotel yang diurus MISTBORNE (sistem all-inclusive): foto, bintang, area, fasilitas, dan lokasi di Google Maps.
     ringkas=true untuk kolom sempit (panel ringkasan booking): thumbnail berderet di bawah foto, bukan kolom di samping. --}}
<div {{ $attributes->class(['grid gap-4']) }}>
    @foreach ($hotel as $item)
        <article class="overflow-hidden rounded-3xl border border-[#E3E7E2] bg-white">
            @php
                $kumpulanFoto = collect($item['foto'] ? [['url' => $item['foto'], 'label' => null]] : [])->merge($item['galeri'] ?? [])->values();
            @endphp
            @if ($kumpulanFoto->isNotEmpty())
                <div x-data="{ aktif: 0, foto: @js($kumpulanFoto->all()) }" @class(['grid gap-2 p-2', 'sm:grid-cols-[minmax(0,1fr)_6.5rem]' => $kumpulanFoto->count() > 1 && ! $ringkas])>
                    <div class="relative overflow-hidden rounded-xl">
                        <img src="{{ $kumpulanFoto[0]['url'] }}" :src="foto[aktif].url" alt="{{ $item['nama'] }}" class="aspect-[16/10] w-full object-cover" width="1600" height="1000" loading="lazy" decoding="async">
                        <span x-show="foto[aktif].label" x-text="foto[aktif].label" x-cloak class="absolute bottom-3 start-3 rounded-full bg-[#12301F]/80 px-3 py-1 text-xs font-medium text-white"></span>
                    </div>
                    @if ($kumpulanFoto->count() > 1)
                        <div class="relative">
                            <ul @class(['flex gap-2 overflow-x-auto', 'sm:absolute sm:inset-0 sm:flex-col sm:overflow-y-auto sm:overflow-x-hidden' => ! $ringkas]) aria-label="{{ __('Galeri foto hotel') }}">
                                @foreach ($kumpulanFoto as $indeks => $foto)
                                    <li @class(['shrink-0', 'sm:w-full' => ! $ringkas])>
                                        <button type="button" x-on:click="aktif = {{ $indeks }}" :aria-current="aktif === {{ $indeks }}" aria-label="{{ $foto['label'] ?? $item['nama'] }}" :class="aktif === {{ $indeks }} ? 'ring-2 ring-[#1D7A56] ring-offset-1' : 'opacity-80 hover:opacity-100'" @class(['block overflow-hidden rounded-xl transition', 'w-20 sm:w-full' => ! $ringkas, 'w-16' => $ringkas])>
                                            <img src="{{ $foto['url'] }}" alt="{{ $foto['label'] ?? $item['nama'] }}" class="aspect-[4/3] w-full object-cover" width="160" height="120" loading="lazy" decoding="async">
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @else
                <div class="flex aspect-[16/10] w-full items-center justify-center bg-[#EAF3EE] text-[#1D7A56]" aria-hidden="true"><span class="material-symbols-outlined text-5xl">hotel</span></div>
            @endif
            <div @class(['p-5', 'sm:p-6' => ! $ringkas])>
                <p class="flex items-center gap-2 text-sm text-[#5B6560]"><span class="tracking-widest text-[#C9A24B]" role="img" aria-label="{{ __('Hotel bintang :n', ['n' => $item['bintang']]) }}">{{ str_repeat('★', $item['bintang']) }}</span><span aria-hidden="true">·</span>{{ $item['area'] }}</p>
                <h3 @class(['mt-1.5 font-semibold leading-tight tracking-tight text-[#141414]', 'text-2xl' => ! $ringkas, 'text-xl' => $ringkas])><a href="{{ $item['url'] }}" class="transition-colors hover:text-[#1D7A56]">{{ $item['nama'] }}</a></h3>
                @if (! empty($item['jarak']))
                    <p class="mt-5 flex items-start gap-3 text-[15px] leading-snug text-[#141414]">
                        <span class="material-symbols-outlined mt-0.5 text-xl text-[#1D7A56]" aria-hidden="true">schedule</span>
                        <span><span class="font-semibold">{{ __('Estimasi perjalanan destinasi–hotel') }}:</span> {{ $item['jarak'] }}<span class="mt-0.5 block text-xs text-[#7A847E]">{{ __('Estimasi saja; bisa berubah karena lalu lintas dan kondisi jalan.') }}</span></span>
                    </p>
                @endif
                @if (! empty($item['deskripsi']))
                    <p class="mt-5 text-[15px] leading-[1.75] text-[#3A4A40]">{{ $item['deskripsi'] }}</p>
                @endif
                @if ($item['fasilitas'])
                    <p class="mt-6 text-sm font-semibold text-[#141414]">{{ __('Fasilitas yang didapat') }}</p>
                    <ul class="mt-3 flex flex-wrap gap-x-5 gap-y-2.5">
                        @foreach ($item['fasilitas'] as $indeksFasilitas => $fasilitas)
                            <li class="inline-flex items-center gap-1.5 text-[13px] font-medium text-[#3E4743]">
                                @if (isset($item['fasilitas_kunci'][$indeksFasilitas]))
                                    <x-bersama.ikon-fasilitas :kunci="$item['fasilitas_kunci'][$indeksFasilitas]" class="h-4 w-4 text-[#1D7A56]" />
                                @endif
                                {{ $fasilitas }}
                            </li>
                        @endforeach
                    </ul>
                @endif
                <div class="mt-6 flex flex-wrap gap-3 pt-1">
                    <a href="{{ $item['url'] }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-[#1D7A56] px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-[#12301F] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1D7A56] focus-visible:ring-offset-2">{{ __('Lihat detail hotel') }}<span class="material-symbols-outlined text-lg" aria-hidden="true">arrow_forward</span></a>
                    <a href="{{ $item['peta'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-full bg-[#F0F2EF] px-5 py-2.5 text-sm font-medium text-[#141414] transition-colors duration-200 hover:bg-[#E3E7E2] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1D7A56]"><span class="material-symbols-outlined text-lg" aria-hidden="true">map</span>{{ __('Lihat lokasi di Google Maps') }}</a>
                </div>
            </div>
        </article>
    @endforeach
</div>
