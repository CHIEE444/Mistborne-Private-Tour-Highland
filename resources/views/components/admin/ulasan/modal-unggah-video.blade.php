@props(['ulasan'])

<x-admin.modal :nama="'unggah-video-'.$ulasan->id" size="sm">
    <x-slot:header>
        <p class="font-semibold text-ink">Unggah Video Testimoni Tamu</p>
        <p class="text-xs text-muted">Lampirkan dokumentasi audiovisual resmi untuk tampilan homepage.</p>
    </x-slot:header>

    <form
        id="form-unggah-video-{{ $ulasan->id }}"
        method="POST"
        action="{{ route('admin.ulasan.video.unggah', $ulasan) }}"
        enctype="multipart/form-data"
    >
        @csrf

        <div class="mb-4 flex items-center justify-between rounded-lg bg-page p-3">
            <div>
                <p class="text-sm font-medium text-ink">{{ $ulasan->pesanan?->nama_lengkap }}</p>
                <p class="text-xs text-muted">{{ $ulasan->pesanan?->kode }} &middot; {{ $ulasan->pesanan?->nama_perjalanan }}</p>
            </div>
            @if ($ulasan->izin_tampil)
                <span class="rounded-full bg-status-completed-bg px-2 py-0.5 text-[11px] font-semibold text-status-completed-teks">Izin Publikasi: Ya</span>
            @else
                <span class="rounded-full bg-status-dibatalkan-bg px-2 py-0.5 text-[11px] font-semibold text-status-dibatalkan-teks">Izin Publikasi: Tidak</span>
            @endif
        </div>

        <p class="mb-3 text-xs text-muted">
            Format yang didukung: <strong>MP4</strong> atau <strong>MOV</strong>. Ukuran maksimal
            <strong>{{ \App\Models\Pengaturan::current()->batasVideoEfektifMb() }} MB</strong>@if (\App\Models\Pengaturan::current()->batasVideoEfektifMb() < \App\Models\Pengaturan::current()->batas_video_mb)
                (dibatasi PHP di server ini; pengaturan aplikasi {{ \App\Models\Pengaturan::current()->batas_video_mb }} MB)@endif.
        </p>

        <x-admin.unggah-berkas name="video" accept="video/mp4,video/quicktime" keterangan="Resolusi ideal 1080p (16:9 atau 9:16)" />
    </form>

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>
        <x-admin.tombol type="submit" form="form-unggah-video-{{ $ulasan->id }}" icon="upload">Simpan &amp; Lampirkan Video</x-admin.tombol>
    </x-slot:footer>
</x-admin.modal>
