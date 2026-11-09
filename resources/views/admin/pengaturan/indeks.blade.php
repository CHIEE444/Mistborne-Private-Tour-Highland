<x-tata-letak.admin title="{{ __('admin.sidebar.pengaturan') }}" group="{{ __('admin.sidebar.grup_sistem') }}">
    <x-admin.kepala-halaman
        :title="__('admin.sidebar.pengaturan')"
        subtitle="Konfigurasi parameter operasional global, nomor WhatsApp admin, dan batasan reservasi"
    />

    <x-admin.kartu class="mb-6 flex items-start gap-4 bg-page">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-muted">
            <span class="material-symbols-outlined" aria-hidden="true">schedule</span>
        </span>
        <div class="text-sm">
            <p class="font-semibold text-ink">Zona Waktu Sistem: Asia/Jakarta (WIB)</p>
            <p class="mt-1 text-muted">
                Pengingat H-7 (batas perubahan jadwal), H-3 (persiapan tamu), H-2 (detail jemputan), dan peringatan H-1
                dievaluasi setiap pukul <span class="font-medium text-ink">00:01 WIB</span>.
            </p>
        </div>
    </x-admin.kartu>

    <form method="POST" action="{{ route('admin.pengaturan.perbarui') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-admin.kartu>
            <h2 class="text-sm font-semibold text-ink">Kontak &amp; Gateway</h2>
            <p class="mb-4 text-xs text-muted">Nomor ini (format 62…) dipakai di semua tombol wa.me dan pengingat broadcast otomatis.</p>

            <x-admin.formulir.masukan
                name="nomor_whatsapp_admin"
                label="Nomor WhatsApp Admin"
                icon="chat"
                :value="$pengaturan->nomor_whatsapp_admin"
                placeholder="Format 62…, dipakai di semua tombol wa.me"
                wajib
                class="max-w-xs"
            />
        </x-admin.kartu>

        <x-admin.kartu>
            <h2 class="text-sm font-semibold text-ink">Aturan Pesanan &amp; Keuangan</h2>
            <p class="mb-4 text-xs text-muted">Kebijakan reschedule, masa hold ketersediaan, dan batas kuota</p>

            <div class="grid gap-4 sm:grid-cols-3">
                <x-admin.formulir.angka-satuan
                    name="persen_charge_ganti_jadwal"
                    label="Charge Ganti Jadwal (reschedule pertama di atas H-7)"
                    satuan="%"
                    :value="$pengaturan->persen_charge_ganti_jadwal"
                    wajib
                />
                <x-admin.formulir.angka-satuan
                    name="batas_bayar_hari"
                    label="Batas Waktu Bayar"
                    satuan="Hari"
                    :value="$pengaturan->batas_bayar_hari"
                    wajib
                />
                <x-admin.formulir.angka-satuan
                    name="kuota_default"
                    label="Kuota Grup per Tanggal"
                    satuan="Grup"
                    :value="$pengaturan->kuota_default"
                    wajib
                />
            </div>

            <x-admin.peringatan type="info" class="mt-4">
                Tamu yang belum transfer sama sekali sampai Batas Waktu Bayar muncul di peringatan dashboard; {{ config('mistborne.pembayaran.tenggang_batal_hari', 2) }} hari kemudian pesanannya dibatalkan otomatis dan kuotanya dibebaskan. Perubahan kuota hanya berlaku untuk tanggal yang belum punya kuota khusus.
            </x-admin.peringatan>
        </x-admin.kartu>

        <x-admin.kartu>
            <h2 class="text-sm font-semibold text-ink">Ulasan &amp; Media</h2>
            <p class="mb-4 text-xs text-muted">Regulasi formulir umpan balik dan aset digital testimoni</p>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.formulir.angka-satuan
                    name="batas_ulasan_hari"
                    label="Masa Aktif Tautan Ulasan (setelah trip selesai)"
                    satuan="Hari"
                    :value="$pengaturan->batas_ulasan_hari"
                    wajib
                />
                @php
                    $batasServerMb = \App\Bantuan\BatasUnggah::serverMb();
                    $labelVideo = 'Batas Ukuran Video Testimoni';
                    if ($batasServerMb !== null) {
                        $labelVideo .= ' (server maks. '.$batasServerMb.' MB)';
                    }
                @endphp
                <x-admin.formulir.angka-satuan
                    name="batas_video_mb"
                    :label="$labelVideo"
                    satuan="MB"
                    :value="$pengaturan->batas_video_mb"
                    wajib
                />
            </div>
        </x-admin.kartu>

        <x-admin.kartu>
            <h2 class="text-sm font-semibold text-ink">Rekomendasi Penginapan</h2>
            <p class="mb-4 text-xs text-muted">Dikirim ke tamu yang belum punya penginapan lewat tombol &ldquo;Kirim Rekomendasi Penginapan&rdquo;.</p>

            <x-admin.formulir.area-teks
                name="rekomendasi_penginapan"
                label="Daftar rekomendasi"
                :value="$pengaturan->rekomendasi_penginapan"
                rows="6"
                placeholder="Tulis satu penginapan per baris beserta area dan kisaran harga. Contoh:&#10;• Hotel Bromo Permai, Cemoro Lawang (± Rp 450.000/malam)&#10;• Java Banana Homestay, Ngadisari (± Rp 300.000/malam)&#10;Dibaca langsung oleh tamu, jadi pastikan sudah benar."
            />
        </x-admin.kartu>

        <div class="flex justify-end gap-3">
            <x-admin.tombol as="a" href="{{ route('admin.pengaturan.indeks') }}" variant="secondary">Batal</x-admin.tombol>
            <x-admin.tombol type="submit" icon="check_circle">Simpan Pengaturan</x-admin.tombol>
        </div>
    </form>
    <x-admin.kartu class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-page text-muted">
                <span class="material-symbols-outlined" aria-hidden="true">logout</span>
            </span>
            <div class="text-sm">
                <p class="font-semibold text-ink">Keluar dari Admin Panel</p>
                <p class="mt-1 text-muted">Akhiri sesi di perangkat ini. Anda perlu masuk lagi untuk membuka admin panel.</p>
            </div>
        </div>
        <x-admin.tombol
            type="button"
            variant="danger-outline"
            icon="logout"
            x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'konfirmasi-keluar' } }))"
        >Keluar</x-admin.tombol>
    </x-admin.kartu>
</x-tata-letak.admin>
