@props([
    'name',
    'accept' => null,
    'keterangan' => null,
    'pratinjauGambar' => false,
    'maksimalMb' => null,
    'pesanWajib' => null,
])

@php($namaModalPratinjau = 'pratinjau-'.\Illuminate\Support\Str::slug($name))

<div x-data="unggahBerkas(@js($pratinjauGambar), @js($maksimalMb))">
    <label
        x-on:dragover.prevent="tandaiMenyeret(true)"
        x-on:dragleave.prevent="tandaiMenyeret(false)"
        x-on:drop.prevent="jatuhkanBerkas($event)"
        :class="menyeret ? 'border-hijau-aksi bg-status-completed-bg' : 'border-surface-border'"
        class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-admin-kartu border-2 border-dashed px-6 py-10 text-center transition-colors"
    >
        <span class="material-symbols-outlined text-3xl text-muted" aria-hidden="true">cloud_upload</span>

        <p class="text-sm text-ink">
            Tarik &amp; lepas berkas di sini atau <span class="font-semibold text-hijau-aksi">klik untuk pilih</span>
        </p>

        @if ($keterangan)
            <p class="text-xs text-muted">{{ $keterangan }}</p>
        @endif

        <p class="text-xs font-medium text-ink" x-show="namaBerkas" x-text="namaBerkas" x-cloak></p>

        <input
            type="file"
            name="{{ $name }}"
            x-ref="input"
            @if ($accept) accept="{{ $accept }}" @endif
            x-on:change="pilihDariInput($event)"
            data-kunci="{{ $name }}"
            @if ($pesanWajib) data-wajib="{{ $pesanWajib }}" @endif
            {{ $attributes->class(['hidden']) }}
        />
    </label>

    @if ($pratinjauGambar)
        <div x-show="urlBerkas" x-cloak class="mt-3 flex items-center gap-3 rounded-lg border border-surface-border p-3">
            <template x-if="adalahGambar">
                <button
                    type="button"
                    x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: '{{ $namaModalPratinjau }}' } }))"
                    class="shrink-0 overflow-hidden rounded-md border border-surface-border focus:outline-none focus:ring-2 focus:ring-hijau-aksi"
                    aria-label="Perbesar pratinjau gambar"
                >
                    <img :src="urlBerkas" :alt="`Pratinjau ${namaBerkas}`" class="h-20 w-20 object-cover" />
                </button>
            </template>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-ink" x-text="namaBerkas"></p>
                <p x-show="detailBerkas" x-cloak class="mt-0.5 text-xs text-muted">
                    <span x-text="detailBerkas?.format"></span> ·
                    <span x-text="detailBerkas?.ukuran"></span>
                    <template x-if="detailBerkas?.dimensi"><span> · <span x-text="detailBerkas.dimensi"></span></span></template>
                </p>
                <template x-if="adalahGambar">
                    <button type="button" x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: '{{ $namaModalPratinjau }}' } }))" class="mt-1 text-xs font-medium text-hijau-aksi hover:underline">Klik gambar untuk melihat lebih jelas</button>
                </template>
                <template x-if="!adalahGambar">
                    <a :href="urlBerkas" target="_blank" rel="noopener" class="mt-1 inline-block text-xs font-medium text-hijau-aksi hover:underline">Buka berkas</a>
                </template>
            </div>
        </div>

        <x-admin.modal :nama="$namaModalPratinjau" size="xl">
            <div class="flex justify-center">
                <img x-show="adalahGambar" :src="urlBerkas" :alt="`Pratinjau ${namaBerkas}`" class="max-h-[80vh] max-w-full rounded-md object-contain" />
            </div>
        </x-admin.modal>
    @endif

    @error($name)
        <x-admin.formulir.galat :pesan="$message" class="mt-2" />
    @enderror

    <p x-cloak x-show="$data.galat?.['{{ $name }}']" class="mt-2 flex items-center gap-1 text-xs text-status-dibatalkan-teks" role="alert">
        <span class="material-symbols-outlined text-sm" aria-hidden="true">error</span>
        <span x-text="$data.galat?.['{{ $name }}']"></span>
    </p>
</div>
