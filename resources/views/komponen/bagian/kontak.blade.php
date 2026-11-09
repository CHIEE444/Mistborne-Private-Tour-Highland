{{-- KONTAK (#kontak): formulir konsultasi yang tenang — latar putih, kolom kiri (judul, foto journey terpilih, poin penenang,
     hotline) dan lembar formulir bergaya editorial di kanan. Rombongan melebihi kapasitas diarahkan ke Trip Kustom; kirim →
     WhatsApp admin dan tercatat sebagai pertanyaan tamu (Alpine contactForm di app.js). Desain sebelumnya dicadangkan di
     storage/app/private/cadangan-desain. --}}
@php
    // Pilihan form: semua journey (tetap bisa dipesan lewat /journey) + paket yang masih ditampilkan. Paket yang
    // disembunyikan admin tidak boleh muncul (beserta harganya) di beranda.
    $journeyData = \App\Models\Perjalanan::query()->with('kendaraan')->where('template_pesanan_kustom', false)
        ->where(fn ($q) => $q->journey()->orWhere(fn ($paket) => $paket->paket()->tampilBeranda()))
        ->orderBy('urutan')->get();
    $journeys = $journeyData->map(function (\App\Models\Perjalanan $j): array {
        $publik = $j->kePublik();

        return [
            'value' => $j->nama,
            'grup' => $j->template_pesanan_kustom || $publik['jadwal_fleksibel'] ? 'kustom' : (($publik['jenis'] ?? 'journey') === 'paket' ? 'paket' : 'journey'),
            'name' => \Illuminate\Support\Str::title(mb_strtolower($j->nama)),
            'sub' => $j->tempat ? __($j->tempat) : '',
            'img' => $publik['img'],
            'pos' => 'center 40%',
            'maks' => $j->maks_orang ?? 15,
            'durasi' => $publik['duration'],
            'kapasitas' => $publik['capacity'],
            'harga' => $publik['jadwal_fleksibel'] ? __('Harga disesuaikan') : ($publik['price'] ? __('Mulai dari').' '.$publik['price'] : __('Harga sesuai rute')),
        ];
    })->all();
    if ($journeys === []) {
        $journeys = [['value' => 'DIENG HIGHLAND ESCAPE', 'grup' => 'journey', 'name' => 'Dieng Highland Escape', 'sub' => '', 'img' => '/images/dieng-potret.jpg', 'pos' => 'center 40%', 'maks' => 15, 'durasi' => '', 'kapasitas' => '', 'harga' => '']];
    }

    $waLabels = [
        'prefix' => __('Halo tim Mistborne, saya ingin berkonsultasi untuk perjalanan privat. Mohon bantuannya, berikut detail permintaan saya:'),
        'nama' => __('Nama Lengkap'),
        'whatsapp' => __('Nomor WhatsApp'),
        'journey' => __('Pilihan Perjalanan'),
        'tanggal' => __('Tanggal Perjalanan'),
        'wisatawan' => __('Jumlah Peserta'),
        'hotel' => __('Nama Hotel'),
        'penginapan' => __('Penginapan'),
        'penginapanBelum' => __('Belum punya, mohon rekomendasi penginapan'),
        'penginapanAllIn' => __('All-inclusive (penginapan dan tiket masuk diurus MISTBORNE)'),
        'lokasi' => __('Alamat / titik jemput'),
        'jam' => __('Jam jemput'),
        'kustomRombongan' => __('diproses sebagai Trip Kustom (rombongan besar)'),
        'catatan' => __('Permintaan Khusus'),
        'judulRombongan' => __('Rombongan :n orang'),
        'isiRombongan' => __('Perjalanan ini menampung maksimal :m tamu. Agar seluruh rombongan tetap nyaman, permintaan Anda kami proses sebagai Trip Kustom: kendaraan, rute, dan harga disesuaikan.'),
        'kembaliRombongan' => __('Kembali ke :m orang'),
        'orang' => __('orang'),
        'tanggalMin' => now()->addDays(7)->toDateString(),
        'tanggalMax' => now()->addDays(7)->addYear()->toDateString(),
    ];

    // Ekspresi Alpine: true saat journey yang dipilih adalah Custom Highland.
    $K = "form.journey === 'CUSTOM HIGHLAND JOURNEY'";

    // Nomor hotline mengikuti pengaturan admin (format +62 xxx-xxxx-xxxx).
    $nomorAdmin = preg_replace('/\D+/', '', (string) \App\Models\Pengaturan::current()->nomor_whatsapp_admin);
    $hotlineTampil = strlen($nomorAdmin) >= 10
        ? '+'.substr($nomorAdmin, 0, 2).' '.substr($nomorAdmin, 2, 3).'-'.substr($nomorAdmin, 5, 4).'-'.substr($nomorAdmin, 9)
        : '+'.$nomorAdmin;
@endphp
<style>
    [x-cloak] { display: none !important; }
    .wa, .wa *:not(.material-symbols-outlined) { font-family: inherit; }

    /* Latar putih polos seperti bagian lain di beranda */
    .wa { background: #fff; }
    .wa-tata { display: grid; grid-template-columns: minmax(0, 1fr); gap: 40px; }

    /* Kolom kiri: judul, foto journey terpilih, poin penenang, hotline */
    .wa-bagian__label { margin: 0; font-size: 15px; color: #6B736F; }
    .wa-judul { margin: 12px 0 0; font-size: clamp(36px, 4.4vw, 60px); line-height: 1.02; font-weight: 600; letter-spacing: -.035em; color: #1C2421; }
    .wa-sub { margin: 18px 0 0; max-width: 440px; font-size: 15px; line-height: 1.7; color: #6B736F; }
    .wa-foto { position: relative; isolation: isolate; margin-top: 32px; aspect-ratio: 16 / 10; overflow: hidden; border-radius: 24px; background: #DCE4D8; color: #fff; }
    .wa-foto__img { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; transform: scale(1.04); transition: opacity .7s ease, transform 1.6s cubic-bezier(.22,1,.36,1); }
    .wa-foto__img.is-aktif { transform: scale(1); }
    .wa-foto::after { content: ''; position: absolute; inset: 0; z-index: -1; pointer-events: none; background: linear-gradient(180deg, rgba(10,16,13,0) 45%, rgba(10,16,13,.62) 100%); }
    .wa-foto__isi { position: absolute; inset: auto 16px 16px 16px; padding: 14px 16px; border-radius: 16px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.3); -webkit-backdrop-filter: blur(16px) saturate(140%); backdrop-filter: blur(16px) saturate(140%); }
    .wa-meta { margin: 0; font-size: 12.5px; font-weight: 500; color: rgba(255,255,255,.85); }
    .wa-nama { margin: 3px 0 0; font-size: 19px; font-weight: 600; line-height: 1.2; letter-spacing: -.015em; }
    .wa-harga { margin: 2px 0 0; font-size: 13.5px; color: rgba(255,255,255,.88); }
    .wa-tenang { display: grid; gap: 12px; margin: 28px 0 0; padding: 0; list-style: none; }
    .wa-tenang li { display: flex; align-items: center; gap: 12px; font-size: 14.5px; color: #3F4743; }
    .wa-tenang__cek { display: grid; place-items: center; flex: none; width: 24px; height: 24px; border-radius: 999px; background: #DDEBD3; color: #1C2421; }
    .wa-tenang__cek svg { width: 13px; height: 13px; fill: none; stroke: currentColor; stroke-width: 2.6; stroke-linecap: round; stroke-linejoin: round; }
    .wa-hotline { display: inline-flex; flex-direction: column; gap: 2px; margin-top: 28px; padding-top: 20px; border-top: 1px solid #E1E7DE; text-decoration: none; }
    .wa-hotline__label { font-size: 12.5px; color: #6B736F; }
    .wa-hotline__nomor { font-size: 17px; font-weight: 600; color: #1C2421; text-decoration: underline; text-decoration-color: #C9D6C1; text-underline-offset: 5px; transition: text-decoration-color .2s; }
    .wa-hotline:hover .wa-hotline__nomor { text-decoration-color: #1C2421; }

    /* Lembar formulir: putih, garis tipis, kolom bergaya editorial (label di atas + garis bawah) */
    .wa-form { display: flex; flex-direction: column; gap: 26px; padding: 28px 22px; border-radius: 24px; background: #fff; border: 1px solid #E5EAE2; box-shadow: 0 30px 60px -50px rgba(28,36,33,.35); }
    .wa-kepala { display: grid; gap: 12px; }
    .wa-kepala__atas { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; }
    .wa-form__judul { margin: 0; font-size: 22px; font-weight: 600; letter-spacing: -.02em; color: #1C2421; }
    .wa-progres__teks { margin: 0; font-size: 12.5px; color: #6B736F; white-space: nowrap; }
    .wa-progres__teks b { font-weight: 600; color: #1C2421; }
    .wa-progres { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 6px; margin: 0; padding: 0; list-style: none; }
    .wa-progres li { height: 3px; border-radius: 3px; background: #E7ECE4; transition: background-color .4s ease; }
    .wa-progres li.is-selesai { background: #7FA287; }

    /* Pilih perjalanan: pil berfoto bulat mini, satu baris yang bisa digeser */
    .wa-pilih { min-width: 0; }
    .wa-pilih__atas { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .wa-geser-tombol { display: flex; gap: 6px; }
    .wa-step { display: inline-grid; place-items: center; width: 32px; height: 32px; border: 0; border-radius: 999px; background: #fff; color: #1C2421; cursor: pointer; box-shadow: inset 0 0 0 1px #DCE2DA; transition: background-color .2s, color .2s, box-shadow .2s; }
    .wa-step svg { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; }
    .wa-step .material-symbols-outlined { font-size: 20px; }
    .wa-step:hover:not(:disabled) { background: #1C2421; color: #fff; box-shadow: none; }
    .wa-step:disabled { color: #C3CBC5; cursor: default; box-shadow: inset 0 0 0 1px #E8ECE6; }
    .wa-geser { display: flex; gap: 8px; overflow-x: auto; scroll-snap-type: x proximity; padding: 2px 2px 6px; margin: -2px -2px 0; scrollbar-width: none; -webkit-mask-image: linear-gradient(to right, #000 86%, transparent); mask-image: linear-gradient(to right, #000 86%, transparent); }
    .wa-geser::-webkit-scrollbar { display: none; }
    .wa-jk { position: relative; display: inline-flex; flex: none; align-items: center; gap: 10px; height: 46px; padding: 0 16px 0 6px; border-radius: 999px; background: #fff; box-shadow: inset 0 0 0 1px #E1E7DE; color: #3F4743; font-size: 13.5px; font-weight: 500; white-space: nowrap; cursor: pointer; scroll-snap-align: start; transition: background-color .25s, box-shadow .25s, color .25s; }
    .wa-jk img { width: 34px; height: 34px; flex: none; border-radius: 999px; object-fit: cover; }
    .wa-jk:hover { box-shadow: inset 0 0 0 1px #BFCDBA; }
    .wa-jk.is-aktif { background: #DDEBD3; box-shadow: inset 0 0 0 1px #C3D6B8; color: #1C2421; font-weight: 600; }
    .wa-jk:has(input:focus-visible) { outline: 2px solid #1C2421; outline-offset: 2px; }

    /* Kolom isian: label kecil di atas, isian di bawah, garis bawah tipis yang menebal gelap saat diisi */
    .wa-kolom { display: grid; grid-template-columns: minmax(0, 1fr); gap: 22px 32px; }
    .wa-f { position: relative; display: flex; align-items: flex-end; gap: 12px; padding: 0 0 10px; box-shadow: inset 0 -1px 0 #DCE2DA; transition: box-shadow .25s; }
    .wa-f:hover { box-shadow: inset 0 -1px 0 #A9B6A6; }
    .wa-f:focus-within { box-shadow: inset 0 -2px 0 #1C2421; }
    .wa-f.is-invalid { box-shadow: inset 0 -2px 0 #E59A7C; }
    .wa-f.is-kustom { box-shadow: inset 0 -2px 0 #E3A65A; }
    .wa-f__isi { flex: 1; min-width: 0; }
    .wa-label { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; font-size: 12.5px; font-weight: 500; color: #6B736F; cursor: pointer; white-space: nowrap; overflow: hidden; }
    .wa-label__opsional { font-weight: 400; color: #A3ABA5; }
    .wa-label__galat { font-weight: 500; color: #C2410C; white-space: nowrap; }
    .wa-label__kustom { padding: 1px 8px; border-radius: 999px; background: #FCE8CC; color: #9A4A0B; font-size: 11px; font-weight: 600; }
    .wa-input { width: 100%; margin-top: 6px; padding: 0; border: 0; background: transparent; font-size: 16px; line-height: 1.5; color: #1C2421; outline: none; }
    .wa-input::placeholder { color: #B1B8B2; font-weight: 400; }
    .wa-input:not(:placeholder-shown) { font-weight: 500; }
    .wa-input:focus { outline: none; box-shadow: none; }
    textarea.wa-input { min-height: 52px; resize: none; }
    .wa-tampil { display: block; margin-top: 6px; font-size: 16px; font-weight: 500; line-height: 1.5; color: #1C2421; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .wa-tampil.is-kosong { font-weight: 400; color: #B1B8B2; }
    .wa-pemilih { position: absolute; inset: 0; z-index: 2; width: 100%; height: 100%; margin: 0; padding: 0; border: 0; opacity: 0; cursor: pointer; -webkit-appearance: none; appearance: none; }
    .wa-pemilih::-webkit-calendar-picker-indicator { position: absolute; inset: 0; width: auto; height: auto; cursor: pointer; }
    .wa-f__ok { display: grid; place-items: center; flex: none; width: 20px; height: 20px; margin-bottom: 3px; border-radius: 999px; background: #DDEBD3; color: #1C2421; }
    .wa-f__ok svg { width: 12px; height: 12px; fill: none; stroke: currentColor; stroke-width: 2.8; stroke-linecap: round; stroke-linejoin: round; }
    .wa-stepper { display: flex; align-items: center; gap: 6px; flex: none; }
    .wa-jumlah { display: inline-flex; align-items: baseline; gap: 6px; margin-top: 6px; font-size: 16px; color: #1C2421; }
    .wa-jumlah input { width: 28px; border: 0; padding: 0; background: transparent; font-size: 16px; font-weight: 600; color: #1C2421; outline: none; -moz-appearance: textfield; }
    .wa-jumlah input::-webkit-outer-spin-button, .wa-jumlah input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .wa-jumlah span { font-size: 14px; color: #6B736F; }

    /* Tombol kirim: pil mint yang tenang, menggelap saat disorot */
    .wa-kirim { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; height: 56px; border: 0; border-radius: 999px; background: #DDEBD3; color: #1C2421; font-size: 15px; font-weight: 600; cursor: pointer; transition: background-color .25s, color .25s; }
    .wa-kirim:hover { background: #1C2421; color: #fff; }
    .wa-kirim__panah { width: 17px; height: 17px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; transition: transform .25s; }
    .wa-kirim:hover .wa-kirim__panah { transform: translate(2px, -2px); }
    .wa-kirim:focus-visible { outline: 2px solid #1C2421; outline-offset: 3px; }
    .wa-catatan { margin: -12px 0 0; text-align: center; font-size: 12.5px; line-height: 1.6; color: #8A938D; }

    /* Modal peringatan Trip Kustom */
    .wa-modal { position: fixed; inset: 0; z-index: 120; display: grid; place-items: center; padding: 20px; background: rgba(9,20,14,.5); -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px); }
    .wa-modal__panel { width: 100%; max-width: 420px; padding: 28px 26px 24px; border-radius: 26px; background: #fff; text-align: center; box-shadow: 0 40px 80px -30px rgba(0,0,0,.45); }
    .wa-modal-masuk { animation: wa-modal-masuk .28s cubic-bezier(.22,1,.36,1); }
    @keyframes wa-modal-masuk { from { opacity: 0; transform: translateY(14px) scale(.97); } to { opacity: 1; transform: none; } }
    .wa-modal__ikon { display: inline-grid; place-items: center; width: 58px; height: 58px; border-radius: 18px; color: #9A4A0B; background: #FCE8CC; }
    .wa-modal__judul { margin: 16px 0 0; font-size: 21px; font-weight: 600; letter-spacing: -.02em; color: #1C2421; }
    .wa-modal__isi { margin: 10px 0 0; font-size: 14.5px; line-height: 1.65; color: #6B736F; }
    .wa-modal__aksi { display: grid; gap: 10px; margin-top: 22px; }
    .wa-modal__utama, .wa-modal__kedua { height: 50px; border: 0; border-radius: 999px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background-color .2s, color .2s; }
    .wa-modal__utama { background: #DDEBD3; color: #1C2421; }
    .wa-modal__utama:hover { background: #1C2421; color: #fff; }
    .wa-modal__kedua { background: #F3F6F1; color: #1C2421; }
    .wa-modal__kedua:hover { background: #E6ECE3; }
    .wa-modal__utama:focus-visible, .wa-modal__kedua:focus-visible { outline: 2px solid #1C2421; outline-offset: 3px; }

    @media (min-width: 640px) {
        .wa-form { padding: 40px 40px 36px; border-radius: 28px; }
        .wa-kolom { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
        .wa-f--penuh { grid-column: 1 / -1; }
        .wa-tenang { grid-template-columns: repeat(3, minmax(0, auto)); justify-content: start; gap: 12px 28px; }
    }
    @media (min-width: 1024px) {
        .wa-tata { grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); gap: 64px; align-items: start; }
        .wa-kiri { position: sticky; top: 112px; }
        .wa-foto { aspect-ratio: 5 / 4; }
        .wa-tenang { grid-template-columns: minmax(0, 1fr); }
        .wa-form { padding: 44px 48px 40px; }
    }
    @media (max-width: 379px) {
        .wa-label { white-space: normal; flex-wrap: wrap; }
    }
    @media (prefers-reduced-motion: reduce) {
        .wa-foto__img, .wa-kirim__panah, .wa-progres li { transition: none; transform: none; }
    }
</style>
<section class="wa scroll-mt-16 py-20 md:py-24" id="kontak" aria-labelledby="kontak-judul">
    @php
        $urutanGrup = ['journey' => 0, 'paket' => 1, 'kustom' => 2];
        $journeyUrut = collect($journeys)->sortBy(fn ($j) => $urutanGrup[$j['grup']] ?? 9)->values();
        $langkah = [
            ['label' => __('Perjalanan'), 'syarat' => 'true'],
            ['label' => __('Jadwal'), 'syarat' => 'ok.tanggal'],
            ['label' => __('Penjemputan'), 'syarat' => 'ok.lokasi'],
            ['label' => __('Kontak'), 'syarat' => 'ok.nama && ok.whatsapp'],
        ];
        $centang = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m6 12.5 4 4 8-9"/></svg>';
    @endphp
    <div class="mx-auto max-w-[1320px] px-5 sm:px-8 lg:px-12" x-data="contactForm(@js($waLabels), @js($journeys))">
        <div class="wa-tata">
            {{-- KIRI: judul, foto journey terpilih (berganti halus), poin penenang, hotline --}}
            <div class="wa-kiri" data-reveal="up">
                <p class="wa-bagian__label">{{ __('Kontak') }}</p>
                <h2 class="wa-judul" id="kontak-judul"
                    x-text="{!! $K !!} ? @js(__('Rancang Rute Sendiri')) : @js(__('Pesan Langsung via WhatsApp'))">{{ __('Pesan Langsung via WhatsApp') }}</h2>
                <p class="wa-sub"
                   x-text="{!! $K !!}
                       ? @js(__('Susun rute, durasi, dan titik singgah sesuai keinginan. Harga ditentukan setelah konsultasi dengan admin.'))
                       : @js(__('Konsultasikan perjalanan, jadwal penjemputan, atau ajukan rute khusus.'))">{{ __('Konsultasikan perjalanan, jadwal penjemputan, atau ajukan rute khusus.') }}</p>

                <div class="wa-foto">
                    @foreach ($journeys as $j)
                        <img
                            class="wa-foto__img @if ($loop->first) is-aktif @endif"
                            src="{{ $j['img'] }}"
                            alt="{{ $j['name'] }}"
                            width="800"
                            height="640"
                            loading="lazy"
                            decoding="async"
                            style="object-position: {{ $j['pos'] }}; opacity: {{ $loop->first ? 1 : 0 }};"
                            x-bind:class="{ 'is-aktif': form.journey === '{{ $j['value'] }}' }"
                            x-bind:style="{ opacity: form.journey === '{{ $j['value'] }}' ? 1 : 0 }"
                            x-bind:aria-hidden="form.journey === '{{ $j['value'] }}' ? 'false' : 'true'"
                        />
                    @endforeach
                    <div class="wa-foto__isi" aria-live="polite">
                        <p class="wa-meta" x-text="terpilih ? [terpilih.sub, terpilih.durasi].filter(Boolean).join(' · ') : ''">{{ collect([$journeys[0]['sub'], $journeys[0]['durasi']])->filter()->implode(' · ') }}</p>
                        <p class="wa-nama" x-text="terpilih ? terpilih.name : ''">{{ $journeys[0]['name'] }}</p>
                        <p class="wa-harga" x-text="terpilih ? terpilih.harga : ''">{{ $journeys[0]['harga'] }}</p>
                    </div>
                </div>

                <ul class="wa-tenang" role="list">
                    <li><span class="wa-tenang__cek">{!! $centang !!}</span>{{ __('Konsultasi gratis, tanpa komitmen') }}</li>
                    <li><span class="wa-tenang__cek">{!! $centang !!}</span>{{ __('Admin membalas lewat WhatsApp') }}</li>
                    <li><span class="wa-tenang__cek">{!! $centang !!}</span>{{ __('Data Anda hanya untuk menindaklanjuti permintaan ini') }}</li>
                </ul>

                <a class="wa-hotline" href="tel:+{{ $nomorAdmin }}" aria-label="{{ __('Hotline Khusus') }} {{ $hotlineTampil }}">
                    <span class="wa-hotline__label">{{ __('Hotline Khusus') }}</span>
                    <span class="wa-hotline__nomor" dir="ltr">{{ $hotlineTampil }}</span>
                </a>
            </div>

            {{-- KANAN: lembar formulir bergaya editorial --}}
            <form class="wa-form" @submit.prevent="submit" novalidate data-reveal="up" data-reveal-delay="100">
                <div class="wa-kepala">
                    <div class="wa-kepala__atas">
                        <h3 class="wa-form__judul">{{ __('Detail perjalanan') }}</h3>
                        <p class="wa-progres__teks"><b x-text="[{{ collect($langkah)->pluck('syarat')->implode(', ') }}].filter(Boolean).length">1</b> {{ __('dari 4 terisi') }}</p>
                    </div>
                    <ol class="wa-progres" aria-label="{{ __('Kelengkapan form') }}">
                        @foreach ($langkah as $i => $l)
                            <li x-bind:class="{ 'is-selesai': {{ $l['syarat'] }} }" @class(['is-selesai' => $i === 0])><span class="sr-only">{{ $l['label'] }}</span></li>
                        @endforeach
                    </ol>
                </div>

                <div class="wa-pilih" role="group" aria-labelledby="kontak-pilih-judul">
                    <div class="wa-pilih__atas">
                        <p class="wa-label" id="kontak-pilih-judul">{{ __('Pilih perjalanan') }}</p>
                        <div class="wa-geser-tombol">
                            <button type="button" class="wa-step" aria-label="{{ __('Sebelumnya') }}" x-on:click="$refs.geser.scrollBy({ left: -280, behavior: 'smooth' })"><span class="material-symbols-outlined rtl:-scale-x-100" aria-hidden="true">chevron_left</span></button>
                            <button type="button" class="wa-step" aria-label="{{ __('Berikutnya') }}" x-on:click="$refs.geser.scrollBy({ left: 280, behavior: 'smooth' })"><span class="material-symbols-outlined rtl:-scale-x-100" aria-hidden="true">chevron_right</span></button>
                        </div>
                    </div>
                    <div class="wa-geser" x-ref="geser" role="radiogroup" aria-label="{{ __('Pilih perjalanan') }}">
                        @foreach ($journeyUrut as $j)
                            <label for="kontak-journey-{{ $loop->index }}" class="wa-jk" x-bind:class="{ 'is-aktif': form.journey === @js($j['value']) }" @class(['is-aktif' => $j['value'] === $journeys[0]['value']])>
                                <input id="kontak-journey-{{ $loop->index }}" type="radio" name="journey" class="sr-only text-base" value="{{ $j['value'] }}" x-model="form.journey" @checked($j['value'] === $journeys[0]['value'])>
                                <img src="{{ $j['img'] }}" alt="" width="68" height="68" loading="lazy" decoding="async" style="object-position: {{ $j['pos'] }}">
                                <span>{{ $j['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="wa-kolom">
                    <div class="wa-f" x-bind:class="{ 'is-invalid': touched.tanggal && !ok.tanggal }">
                        <div class="wa-f__isi">
                            <label class="wa-label" for="kontak-ambil">{{ __('Tanggal') }}<span class="wa-label__galat" x-show="touched.tanggal && !ok.tanggal" x-cloak>{{ __('min. H-7') }}</span></label>
                            <span class="wa-tampil is-kosong" x-bind:class="{ 'is-kosong': !form.tanggal }" x-text="form.tanggal ? tanggalTampil(form.tanggal) : @js(__('Pilih tanggal'))" aria-hidden="true">{{ __('Pilih tanggal') }}</span>
                            <input
                                class="wa-pemilih text-base"
                                id="kontak-ambil"
                                name="tanggal_trip"
                                type="date"
                                min="{{ $waLabels['tanggalMin'] }}"
                                max="{{ $waLabels['tanggalMax'] }}"
                                required
                                x-model="form.tanggal"
                                x-on:click="$el.showPicker && $el.showPicker()"
                                x-on:blur="sentuh('tanggal')"
                            />
                        </div>
                        <span class="wa-f__ok" x-show="ok.tanggal" x-cloak>{!! $centang !!}</span>
                    </div>

                    <div class="wa-f">
                        <div class="wa-f__isi">
                            <label class="wa-label" for="kontak-jam">{{ __('Jam jemput') }}<span class="wa-label__opsional">{{ __('opsional') }}</span></label>
                            <span class="wa-tampil is-kosong" x-bind:class="{ 'is-kosong': !form.jam }" x-text="form.jam ? form.jam.replace(':', '.') : @js(__('Pilih jam'))" aria-hidden="true">{{ __('Pilih jam') }}</span>
                            <input class="wa-pemilih text-base" id="kontak-jam" name="waktu_pickup" type="time" x-model="form.jam" x-on:click="$el.showPicker && $el.showPicker()" />
                        </div>
                    </div>

                    <div class="wa-f" x-bind:class="{ 'is-kustom': keKustom }">
                        <div class="wa-f__isi">
                            <label class="wa-label" for="kontak-wisatawan">{{ __('Peserta') }}<span class="wa-label__kustom" x-show="keKustom" x-cloak>{{ __('Kustom') }}</span></label>
                            <span class="wa-jumlah">
                                <input
                                    class="text-base"
                                    id="kontak-wisatawan"
                                    name="jumlah_wisatawan"
                                    type="number"
                                    inputmode="numeric"
                                    min="1"
                                    x-bind:max="batasRombongan"
                                    x-model.number="form.wisatawan"
                                />
                                <span x-text="labels.orang">{{ __('orang') }}</span>
                            </span>
                        </div>
                        <div class="wa-stepper">
                            <button type="button" class="wa-step" aria-label="{{ __('Kurangi jumlah wisatawan') }}" x-bind:disabled="form.wisatawan <= 1" x-on:click="kurangi()"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 12h12"/></svg></button>
                            <button type="button" class="wa-step" aria-label="{{ __('Tambah jumlah wisatawan') }}" x-bind:disabled="form.wisatawan >= batasRombongan" x-on:click="tambah()"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 6v12M6 12h12"/></svg></button>
                        </div>
                    </div>

                    <div class="wa-f" x-bind:class="{ 'is-invalid': touched.lokasi && !ok.lokasi }">
                        <div class="wa-f__isi">
                            <label class="wa-label" for="kontak-lokasi">{{ __('Alamat / titik jemput') }}<span class="wa-label__galat" x-show="touched.lokasi && !ok.lokasi" x-cloak>{{ __('wajib diisi') }}</span></label>
                            <input
                                class="wa-input text-base"
                                id="kontak-lokasi"
                                name="lokasi_pickup"
                                type="text"
                                maxlength="255"
                                autocomplete="street-address"
                                placeholder="{{ __('Nama hotel atau alamat lengkap') }}"
                                required
                                x-model="form.lokasi"
                                x-on:blur="sentuh('lokasi')"
                            />
                        </div>
                        <span class="wa-f__ok" x-show="ok.lokasi" x-cloak>{!! $centang !!}</span>
                    </div>

                    <div class="wa-f" x-bind:class="{ 'is-invalid': touched.nama && !ok.nama }">
                        <div class="wa-f__isi">
                            <label class="wa-label" for="kontak-nama">{{ __('Nama lengkap') }}<span class="wa-label__galat" x-show="touched.nama && !ok.nama" x-cloak>{{ __('wajib diisi') }}</span></label>
                            <input
                                class="wa-input text-base"
                                id="kontak-nama"
                                name="nama_lengkap"
                                type="text"
                                maxlength="255"
                                autocomplete="name"
                                placeholder="{{ __('Sesuai identitas') }}"
                                required
                                x-model="form.nama"
                                x-on:blur="sentuh('nama')"
                            />
                        </div>
                        <span class="wa-f__ok" x-show="ok.nama" x-cloak>{!! $centang !!}</span>
                    </div>

                    <div class="wa-f" x-bind:class="{ 'is-invalid': touched.whatsapp && !ok.whatsapp }">
                        <div class="wa-f__isi">
                            <label class="wa-label" for="kontak-wa">{{ __('Nomor WhatsApp') }}<span class="wa-label__galat" x-show="touched.whatsapp && !ok.whatsapp" x-cloak>{{ __('format salah') }}</span></label>
                            <input
                                class="wa-input text-base"
                                id="kontak-wa"
                                name="whatsapp"
                                type="tel"
                                inputmode="tel"
                                autocomplete="tel"
                                maxlength="24"
                                placeholder="081234567890"
                                required
                                x-model="form.whatsapp"
                                x-on:blur="sentuh('whatsapp')"
                            />
                        </div>
                        <span class="wa-f__ok" x-show="ok.whatsapp" x-cloak>{!! $centang !!}</span>
                    </div>

                    <div class="wa-f wa-f--penuh">
                        <div class="wa-f__isi">
                            <label class="wa-label" for="kontak-catatan"><span x-text="{!! $K !!} ? @js(__('Rute impian')) : @js(__('Permintaan khusus'))">{{ __('Permintaan khusus') }}</span><span class="wa-label__opsional">{{ __('opsional') }}</span></label>
                            <textarea
                                class="wa-input text-base"
                                id="kontak-catatan"
                                name="permintaan_khusus"
                                rows="2"
                                maxlength="1000"
                                placeholder="{{ __('Contoh: tambah sarapan, ganti urutan destinasi, kursi bayi, atau koper besar') }}"
                                x-bind:placeholder="{!! $K !!} ? @js(__('Ceritakan destinasi, titik singgah, durasi, dan suasana perjalanan yang kamu inginkan')) : @js(__('Contoh: tambah sarapan, ganti urutan destinasi, kursi bayi, atau koper besar'))"
                                x-model="form.catatan"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <button class="wa-kirim" type="submit">
                    <x-bersama.ikon-wa />
                    <span x-text="{!! $K !!} ? @js(__('Ajukan Rute Custom')) : @js(__('Kirim ke WhatsApp'))">{{ __('Kirim ke WhatsApp') }}</span>
                    <svg class="wa-kirim__panah rtl:-scale-x-100" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 17 17 7M8 7h9v9"/></svg>
                </button>
                <p class="wa-catatan">{{ __('Anda akan diarahkan ke WhatsApp admin dan bisa menyunting pesan sebelum mengirim.') }}</p>
            </form>
        </div>

        {{-- Modal peringatan: muncul saat jumlah peserta melewati kapasitas journey (rombongan → Trip Kustom) --}}
        <template x-teleport="body">
            <div class="wa-modal" x-show="modalKustom" x-cloak x-transition.opacity x-on:keydown.escape.window="modalKustom = false" x-on:click.self="modalKustom = false" role="dialog" aria-modal="true" aria-labelledby="wa-modal-judul" aria-describedby="wa-modal-isi">
                <div class="wa-modal__panel" x-show="modalKustom" x-transition:enter="wa-modal-masuk" x-effect="if (modalKustom) $nextTick(() => $refs.modalLanjut && $refs.modalLanjut.focus())">
                    <span class="wa-modal__ikon"><x-bersama.ikon-formulir nama="peserta" class="h-7 w-7" /></span>
                    <h3 class="wa-modal__judul" id="wa-modal-judul" x-text="labels.judulRombongan.replace(':n', form.wisatawan)">{{ __('Rombongan :n orang', ['n' => $journeys[0]['maks'] + 1]) }}</h3>
                    <p class="wa-modal__isi" id="wa-modal-isi" x-text="labels.isiRombongan.replace(':m', maksPeserta)"></p>
                    <div class="wa-modal__aksi">
                        <button type="button" class="wa-modal__utama" x-ref="modalLanjut" x-on:click="modalKustom = false">{{ __('Lanjut sebagai Trip Kustom') }}</button>
                        <button type="button" class="wa-modal__kedua" x-on:click="form.wisatawan = maksPeserta; modalKustom = false" x-text="labels.kembaliRombongan.replace(':m', maksPeserta)">{{ __('Kembali ke :m orang', ['m' => $journeys[0]['maks']]) }}</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</section>
