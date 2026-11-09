@props(['nama', 'kendaraan', 'mode' => 'tambah'])

{{--
    Dua pemakaian: mode "tambah" (jadwal baru atau riwayat langsung) dan mode "selesai" (menandai jadwal yang sudah
    dikerjakan; aksi dan jenisnya diisi lewat event `siapkan-servis` dari tombol di panel perawatan).
    Galat dan nilai lama hanya ditampilkan di modal yang barusan dikirim (field tersembunyi _modal).
--}}
@php
    $dikirim = old('_modal') === $nama;
    $galat = fn (string $kolom): ?string => $dikirim ? $errors->first($kolom) : null;
    $nilai = fn (string $kolom, mixed $bawaan = null): mixed => $dikirim ? old($kolom, $bawaan) : $bawaan;
    $kelasInput = fn (string $kolom): string => 'w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted focus:outline-none focus:ring-1 '
        .($galat($kolom) ? 'border-status-dibatalkan-teks focus:border-status-dibatalkan-teks focus:ring-status-dibatalkan-teks' : 'border-surface-border focus:border-hijau-aksi focus:ring-hijau-aksi');
    $selesaiAwal = $mode === 'selesai' || $nilai('status') === \App\Models\ServisKendaraan::SELESAI;
@endphp

<x-admin.modal :nama="$nama" size="lg">
    <x-slot:header>
        <p class="font-semibold text-ink">{{ $mode === 'selesai' ? 'Tandai servis selesai' : 'Tambah catatan servis' }}</p>
        <p class="text-sm text-muted">{{ $kendaraan->plat_nomor }} · {{ $kendaraan->nama }}</p>
    </x-slot:header>

    <form
        id="form-{{ $nama }}"
        method="POST"
        @if ($mode === 'tambah') action="{{ route('admin.kendaraan.servis.simpan', $kendaraan) }}" @endif
        x-data="{
            aksi: @js($mode === 'selesai' ? ($dikirim ? old('_aksi') : null) : null),
            status: @js($mode === 'selesai' ? 'selesai' : ($nilai('status') ?: 'terjadwal')),
            jenis: @js($nilai('jenis')),
            odometer: @js($nilai('odometer_servis')),
        }"
        @if ($mode === 'selesai')
            x-bind:action="aksi"
            x-on:siapkan-servis.window="aksi = $event.detail.aksi; jenis = $event.detail.jenis; odometer = $event.detail.odometer"
        @endif
        class="space-y-4"
    >
        @csrf
        <input type="hidden" name="_modal" value="{{ $nama }}" />
        @if ($mode === 'selesai')
            @method('PUT')
            <input type="hidden" name="_aksi" x-bind:value="aksi" />
            <input type="hidden" name="status" value="selesai" />
        @endif

        <div>
            <label for="{{ $nama }}-jenis" class="mb-1.5 block text-sm font-medium text-ink">Jenis servis <span class="text-status-dibatalkan-teks" aria-hidden="true">*</span></label>
            <input id="{{ $nama }}-jenis" name="jenis" type="text" list="daftar-jenis-servis" maxlength="80" required x-model="jenis" placeholder="Contoh: Ganti oli mesin" class="{{ $kelasInput('jenis') }}" />
            @if ($galat('jenis'))<p class="mt-1.5 text-xs text-status-dibatalkan-teks" role="alert">{{ $galat('jenis') }}</p>@endif
        </div>

        @if ($mode === 'tambah')
            <div>
                <label for="{{ $nama }}-status" class="mb-1.5 block text-sm font-medium text-ink">Status <span class="text-status-dibatalkan-teks" aria-hidden="true">*</span></label>
                <select id="{{ $nama }}-status" name="status" x-model="status" class="{{ $kelasInput('status') }} pe-9">
                    @foreach (\App\Models\ServisKendaraan::STATUS as $kunci => $label)
                        <option value="{{ $kunci }}">{{ $kunci === 'terjadwal' ? 'Terjadwal — akan dikerjakan nanti' : 'Sudah dikerjakan — catat sebagai riwayat' }}</option>
                    @endforeach
                </select>
                @if ($galat('status'))<p class="mt-1.5 text-xs text-status-dibatalkan-teks" role="alert">{{ $galat('status') }}</p>@endif
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2" x-show="status === 'terjadwal'" @if ($selesaiAwal) x-cloak @endif>
            <div>
                <label for="{{ $nama }}-jt-tanggal" class="mb-1.5 block text-sm font-medium text-ink">Jatuh tempo (tanggal) — isi tanggal dan/atau kilometer</label>
                <input id="{{ $nama }}-jt-tanggal" name="jatuh_tempo_tanggal" type="date" value="{{ $nilai('jatuh_tempo_tanggal') }}" x-bind:disabled="status !== 'terjadwal'" class="{{ $kelasInput('jatuh_tempo_tanggal') }}" />
                @if ($galat('jatuh_tempo_tanggal'))<p class="mt-1.5 text-xs text-status-dibatalkan-teks" role="alert">{{ $galat('jatuh_tempo_tanggal') }}</p>@endif
            </div>
            <div>
                <label for="{{ $nama }}-jt-odometer" class="mb-1.5 block text-sm font-medium text-ink">Jatuh tempo (kilometer)</label>
                <input id="{{ $nama }}-jt-odometer" name="jatuh_tempo_odometer" type="number" min="0" max="9999999" value="{{ $nilai('jatuh_tempo_odometer') }}" x-bind:disabled="status !== 'terjadwal'" placeholder="Batas kilometer, contoh: 90000" class="{{ $kelasInput('jatuh_tempo_odometer') }}" />
                @if ($galat('jatuh_tempo_odometer'))<p class="mt-1.5 text-xs text-status-dibatalkan-teks" role="alert">{{ $galat('jatuh_tempo_odometer') }}</p>@endif
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3" x-show="status === 'selesai'" @if (! $selesaiAwal) x-cloak @endif>
            <div>
                <label for="{{ $nama }}-tanggal" class="mb-1.5 block text-sm font-medium text-ink">Tanggal dikerjakan <span class="text-status-dibatalkan-teks" aria-hidden="true">*</span></label>
                <input id="{{ $nama }}-tanggal" name="tanggal_servis" type="date" max="{{ today()->toDateString() }}" value="{{ $nilai('tanggal_servis', $mode === 'selesai' ? today()->toDateString() : null) }}" x-bind:disabled="status !== 'selesai'" class="{{ $kelasInput('tanggal_servis') }}" />
                @if ($galat('tanggal_servis'))<p class="mt-1.5 text-xs text-status-dibatalkan-teks" role="alert">{{ $galat('tanggal_servis') }}</p>@endif
            </div>
            <div>
                <label for="{{ $nama }}-odometer" class="mb-1.5 block text-sm font-medium text-ink">Odometer saat servis</label>
                <input id="{{ $nama }}-odometer" name="odometer_servis" type="number" min="0" max="9999999" x-model="odometer" x-bind:disabled="status !== 'selesai'" placeholder="Kilometer, contoh: 87420" class="{{ $kelasInput('odometer_servis') }}" />
                @if ($galat('odometer_servis'))<p class="mt-1.5 text-xs text-status-dibatalkan-teks" role="alert">{{ $galat('odometer_servis') }}</p>@endif
            </div>
            <div>
                <label for="{{ $nama }}-biaya" class="mb-1.5 block text-sm font-medium text-ink">Biaya (Rp)</label>
                <x-admin.formulir.input-rupiah name="biaya" :id="$nama.'-biaya'" :value="$nilai('biaya')" nullable :invalid="(bool) $galat('biaya')" nonaktif-jika="status !== 'selesai'" placeholder="Angka saja, contoh: 450.000" />
                @if ($galat('biaya'))<p class="mt-1.5 text-xs text-status-dibatalkan-teks" role="alert">{{ $galat('biaya') }}</p>@endif
            </div>
        </div>

        <div>
            <label for="{{ $nama }}-catatan" class="mb-1.5 block text-sm font-medium text-ink">Catatan</label>
            <textarea id="{{ $nama }}-catatan" name="catatan" rows="3" maxlength="1000" placeholder="Opsional: nama bengkel, suku cadang yang diganti, dan lainnya." class="{{ $kelasInput('catatan') }}">{{ $nilai('catatan') }}</textarea>
            @if ($galat('catatan'))<p class="mt-1.5 text-xs text-status-dibatalkan-teks" role="alert">{{ $galat('catatan') }}</p>@endif
        </div>
    </form>

    @if ($mode === 'tambah')
        <datalist id="daftar-jenis-servis">
            @foreach (\App\Models\ServisKendaraan::JENIS_UMUM as $jenisUmum)
                <option value="{{ $jenisUmum }}"></option>
            @endforeach
        </datalist>
    @endif

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>
        <x-admin.tombol type="submit" form="form-{{ $nama }}" icon="check">Simpan</x-admin.tombol>
    </x-slot:footer>
</x-admin.modal>
