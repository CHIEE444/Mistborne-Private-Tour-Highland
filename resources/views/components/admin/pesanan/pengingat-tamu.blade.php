@props(['pesanan'])

{{--
    Pengingat ke tamu di Aksi Cepat (H-7, H-3, H-2), dikirim berurutan lewat tombol kirim satu klik dengan draft
    masing-masing tepat di bawah tombolnya. Konfirmasi ulang ke hotel ikut di baris H-3 bila hotel sudah disetujui.
    Setelah semuanya terkirim, daftar berganti menjadi "Selesai semua".
--}}
@php
    use App\Enums\StatusReservasiHotel;

    $layanan = app(\App\Layanan\PesanWhatsAppLayanan::class);
    $hotel = $pesanan->hotel;
    $perluKonfirmasiUlang = $hotel !== null && $pesanan->status_reservasi_hotel === StatusReservasiHotel::Terkonfirmasi;
    $ulangTerkirim = $pesanan->konfirmasi_ulang_hotel_at !== null;

    $daftar = [
        ['jenis' => 'h7', 'label' => 'H-7', 'hari' => 7, 'teks' => $layanan->pengingatH7($pesanan), 'terkirim' => (bool) $pesanan->pengingat_h7_terkirim],
        ['jenis' => 'h3', 'label' => 'H-3', 'hari' => 3, 'teks' => $layanan->pengingatH3($pesanan), 'terkirim' => (bool) $pesanan->pengingat_h3_terkirim],
        ['jenis' => 'h2', 'label' => 'H-2', 'hari' => 2, 'teks' => $layanan->pengingatH2($pesanan), 'terkirim' => (bool) $pesanan->pengingat_h2_terkirim],
    ];
    $barisSelesai = fn (array $baris): bool => $baris['terkirim'] && ($baris['jenis'] !== 'h3' || ! $perluKonfirmasiUlang || $ulangTerkirim);

    // Aturan tahap sama dengan dashboard (Pesanan::tahapPengingatBerlaku()). Tahap sebelum tahap yang berlaku dan belum
    // terkirim = terlewat: tidak ditawarkan lagi karena isinya sudah tidak sesuai. Sebelum masuk H-7, tahap pertama boleh dikirim lebih awal.
    $urutanTahap = array_column($daftar, 'jenis');
    $tahapBerlaku = $pesanan->tahapPengingatBerlaku();
    $tripSudahTiba = $pesanan->tanggal_trip->copy()->startOfDay()->lte(today());
    $indeksBerlaku = $tripSudahTiba ? count($urutanTahap) : ($tahapBerlaku ? array_search($tahapBerlaku, $urutanTahap, true) : 0);
    $barisTerlewat = fn (array $baris): bool => ! $barisSelesai($baris) && array_search($baris['jenis'], $urutanTahap, true) < $indeksBerlaku;
    $aktif = collect($daftar)->first(fn (array $baris): bool => ! $barisSelesai($baris) && ! $barisTerlewat($baris))['jenis'] ?? null;
    $jumlahSelesai = collect($daftar)->filter($barisSelesai)->count();
    $labelTerlewat = collect($daftar)->filter($barisTerlewat)->pluck('label');
@endphp

<section class="rounded-xl border border-surface-border p-3" aria-labelledby="pengingat-tamu-{{ $pesanan->id }}">
    <div class="mb-2 flex items-center justify-between gap-2">
        <h3 id="pengingat-tamu-{{ $pesanan->id }}" class="text-xs font-semibold uppercase tracking-wide text-muted">Pengingat ke tamu</h3>
        <span class="text-[11px] font-semibold text-muted">{{ $jumlahSelesai }}/3 terkirim</span>
    </div>

    @if ($aktif === null && $labelTerlewat->isEmpty())
        <p class="flex items-center gap-1.5 rounded-lg bg-status-completed-bg px-3 py-2 text-sm font-semibold text-status-completed-teks">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">task_alt</span>
            Selesai semua
        </p>
        <p class="mt-1.5 px-1 text-xs text-muted">Pengingat H-7, H-3, dan H-2 sudah dikirim ke tamu{{ $perluKonfirmasiUlang ? ', dan hotel sudah dikonfirmasi ulang' : '' }}.</p>
    @else
        <ol class="space-y-2">
            @foreach ($daftar as $baris)
                @php
                    $selesai = $barisSelesai($baris);
                    $terlewat = $barisTerlewat($baris);
                    $sekarang = $baris['jenis'] === $aktif;
                    $tanggalKirim = $pesanan->tanggal_trip->copy()->subDays($baris['hari']);
                @endphp
                <li @class([
                    'rounded-lg border p-2.5',
                    'border-hijau-aksi/50 bg-page' => $sekarang,
                    'border-surface-border' => ! $sekarang,
                    'opacity-60' => ! $sekarang && ! $selesai,
                    'border-dashed' => $terlewat,
                ])>
                    <div class="flex items-center justify-between gap-2 text-sm">
                        <span class="flex items-center gap-1.5 font-semibold text-ink">
                            <span @class([
                                'material-symbols-outlined text-base',
                                'text-status-completed-teks' => $selesai,
                                'text-hijau-aksi' => $sekarang,
                                'text-muted' => ! $selesai && ! $sekarang,
                            ]) aria-hidden="true">{{ $selesai ? 'check_circle' : ($terlewat ? 'cancel' : ($sekarang ? 'arrow_forward' : 'schedule')) }}</span>
                            Pengingat {{ $baris['label'] }}
                        </span>
                        <span class="text-[11px] text-muted">{{ $selesai ? 'Terkirim' : ($terlewat ? 'Terlewat' : ($tanggalKirim->lte(today()) ? 'Kirim hari ini' : 'Kirim '.$tanggalKirim->translatedFormat('d M'))) }}</span>
                    </div>

                    @if ($terlewat)
                        <p class="mt-1 text-[11px] leading-4 text-muted">Waktunya sudah lewat, isi pesannya tidak sesuai lagi.</p>
                        <form method="POST" action="{{ route('admin.pesanan.pengingat', [$pesanan, $baris['jenis']]) }}" class="mt-1">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="terkirim" value="1">
                            <button type="submit" class="text-[11px] font-medium text-muted underline hover:text-ink">Sudah dikirim manual? Tandai terkirim</button>
                        </form>
                    @elseif ($selesai)
                        <form method="POST" action="{{ route('admin.pesanan.pengingat', [$pesanan, $baris['jenis']]) }}" class="mt-1">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="terkirim" value="0">
                            <button type="submit" class="text-[11px] font-medium text-muted underline hover:text-ink">Batalkan tanda</button>
                        </form>
                    @elseif ($sekarang)
                        @if (! $baris['terkirim'])
                            <x-admin.pesanan.kirim-whatsapp
                                class="mt-2"
                                :aksi="route('admin.pesanan.pengingat', [$pesanan, $baris['jenis']])"
                                :isian="['terkirim' => 1]"
                                :teks="$baris['teks']"
                                :nomor="$pesanan->whatsapp"
                                penerima="tamu"
                                :label="'Kirim pengingat '.$baris['label']"
                            />
                        @else
                            <p class="mt-1 text-xs text-status-completed-teks">Pengingat {{ $baris['label'] }} ke tamu sudah terkirim.</p>
                        @endif

                        @if ($baris['jenis'] === 'h3' && $perluKonfirmasiUlang && ! $ulangTerkirim)
                            <p class="mt-3 text-xs font-semibold text-ink">Konfirmasi ulang ke hotel</p>
                            <x-admin.pesanan.kirim-whatsapp
                                class="mt-1.5"
                                :aksi="route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'ulang'])"
                                :teks="$layanan->konfirmasiUlangKeHotel($pesanan)"
                                :nomor="$hotel->whatsapp"
                                label="Konfirmasi ulang ke hotel"
                            />
                        @endif
                    @endif
                </li>
            @endforeach
        </ol>
    @endif
</section>
