@php
    $jumlahPerjalananSelesai = \App\Models\Pesanan::query()
        ->where('status', \App\Enums\BookingStatus::Completed->value)
        ->count();
    $rataRatingPublik = \App\Models\Ulasan::query()
        ->tampilPublik()
        ->avg('rating');
    $ratingPelanggan = $rataRatingPublik === null ? 4.8 : round((float) $rataRatingPublik, 1);
    $jumlahPersonelAktif = \App\Models\PersonelOperasional::query()
        ->where('aktif', true)
        ->count();

    $stats = [
        ['value' => (string) $jumlahPerjalananSelesai, 'to' => $jumlahPerjalananSelesai, 'decimals' => 0, 'suffix' => '', 'label' => __('Perjalanan Selesai')],
        ['value' => number_format($ratingPelanggan, 1, '.', ''), 'to' => $ratingPelanggan, 'decimals' => 1, 'suffix' => '/ 5.0', 'label' => __('Rating Pelanggan')],
        ['value' => (string) $jumlahPersonelAktif, 'to' => $jumlahPersonelAktif, 'decimals' => 0, 'suffix' => '', 'label' => __('Pemandu & Sopir Lokal')],
        ['value' => '100', 'to' => 100, 'decimals' => 0, 'suffix' => '%', 'label' => __('Asuransi Penuh')],
    ];
@endphp

<section class="stats-section relative overflow-hidden bg-white pt-14 sm:pt-20 lg:pt-24" id="statistik" aria-labelledby="statistik-judul">
    <h2 class="sr-only" id="statistik-judul">{{ __('Statistik layanan MISTBORNE') }}</h2>
    <div class="mx-auto max-w-[1200px] px-6 max-sm:px-5">
        <dl class="stats-grid">
            @foreach ($stats as $index => $stat)
                <x-komponen.item-statistik :stat="$stat" :index="$index" />
            @endforeach
        </dl>
    </div>

    <figure class="stats-ridge">
        <img
            src="{{ asset('images/kelingking-nusa-penida.jpg') }}"
            srcset="{{ asset('images/kelingking-nusa-penida-1200.jpg') }} 1200w, {{ asset('images/kelingking-nusa-penida.jpg') }} 2400w"
            alt="{{ __('Tebing hijau Pantai Kelingking di Nusa Penida, Bali, di atas laut biru toska') }}"
            loading="lazy"
            decoding="async"
            width="2400"
            height="1350"
        >
        <span class="stats-ridge__tint" aria-hidden="true"></span>
    </figure>
</section>
