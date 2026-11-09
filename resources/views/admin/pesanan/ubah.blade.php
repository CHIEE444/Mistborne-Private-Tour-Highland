@php $judulHalaman = 'Ubah '.$pesanan->kode; @endphp
<x-tata-letak.admin :title="$judulHalaman" group="{{ __('admin.sidebar.grup_operasional') }}">
    <x-admin.kepala-halaman :title="$judulHalaman" subtitle="Ubah data tamu dan perjalanan" />

    @php
        $labelPerubahan = \App\Bantuan\PerubahanDataTamu::KOLOM;
    @endphp
    <form
        id="form-ubah-pesanan"
        method="POST"
        action="{{ route('admin.pesanan.perbarui', $pesanan) }}"
        class="space-y-6"
        x-data="{
            label: @js($labelPerubahan),
            awal: {},
            perubahan: [],
            dikonfirmasi: false,
            formulir() { return document.getElementById('form-ubah-pesanan'); },
            baca() {
                const data = new FormData(this.formulir());
                const hasil = {};
                for (const kolom of Object.keys(this.label)) { hasil[kolom] = String(data.get(kolom) ?? '').trim(); }
                return hasil;
            },
            tampil(kolom, nilai) {
                if (nilai === '') { return '—'; }
                return kolom === 'harga' ? 'Rp ' + Number(nilai).toLocaleString('id-ID') : nilai;
            },
            daftarPerubahan() {
                const sekarang = this.baca();
                return Object.keys(this.label)
                    .filter((kolom) => sekarang[kolom] !== this.awal[kolom])
                    .map((kolom) => ({ label: this.label[kolom], lama: this.tampil(kolom, this.awal[kolom]), baru: this.tampil(kolom, sekarang[kolom]) }));
            },
            init() {
                // Komponen formulir (mis. input rupiah) mengisi nilainya saat inisialisasi; baca nilai awal setelah semuanya siap.
                setTimeout(() => { this.awal = this.baca(); }, 0);
            },
            kirim() {
                if (! this.dikonfirmasi) { return; }
                this.$nextTick(() => this.formulir().requestSubmit());
            },
        }"
        x-on:submit="
            if (! dikonfirmasi) {
                perubahan = daftarPerubahan();
                if (perubahan.length > 0) {
                    $event.preventDefault();
                    window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'konfirmasi-perubahan-data' } }));
                }
            }
        "
    >
        @csrf
        @method('PUT')

        {{-- Lebar penuh dan responsif: 1 kolom di ponsel, 2 di tablet/laptop, 4 di layar lebar agar form tampil landscape. --}}
        <x-admin.kartu>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-admin.formulir.masukan name="nama_lengkap" label="Nama Lengkap" :value="$pesanan->nama_lengkap" wajib />
                <x-admin.formulir.masukan name="whatsapp" label="Nomor WhatsApp" :value="'0'.substr($pesanan->whatsapp, 2)" placeholder="Diawali 08 atau +62, contoh 081234567890" wajib />
                <x-admin.formulir.masukan name="jumlah_wisatawan" type="number" label="Jumlah Wisatawan" :value="$pesanan->jumlah_wisatawan" min="1" max="50" step="1" inputmode="numeric" required placeholder="Angka bulat 1–50 orang" wajib />
                <x-admin.formulir.masukan name="waktu_pickup" type="time" label="Jam jemput" :value="\App\Bantuan\JamPenjemputan::untukInput($pesanan->waktu_pickup)" />
                <x-admin.formulir.masukan name="lokasi_pickup" label="Lokasi Pick-up" :value="$pesanan->lokasi_pickup" wajib class="sm:col-span-2" />
                <x-admin.formulir.pilihan
                    name="kendaraan_pilihan"
                    label="Mobil Destinasi"
                    :pilihan="\App\Bantuan\PilihanKendaraan::pilihanAdmin($pesanan->kendaraan_pilihan)"
                    :value="$pesanan->kendaraan_pilihan"
                    placeholder="Belum ditentukan. Terisi otomatis dari destinasi saat tamu booking. Jumlah tamu saja; guide yang merangkap driver memakai 1 kursi."
                    class="sm:col-span-2"
                />
                <x-admin.formulir.penginapan class="sm:col-span-2 xl:col-span-4 xl:grid-cols-4" :status="$pesanan->status_penginapan" :nama="$pesanan->nama_hotel" :alamat="$pesanan->alamat_hotel" />
                <x-admin.formulir.uang name="harga" label="Harga (kosongkan = pakai harga journey)" :value="$pesanan->harga" nullable placeholder="Kosongkan = harga default journey" />
                <x-admin.formulir.uang name="nominal_dibayar" label="Sudah dibayar" :value="$pesanan->nominal_dibayar" nullable />
                <x-admin.formulir.pilihan name="metode_pembayaran" label="Metode pembayaran" :pilihan="['transfer' => 'Transfer bank', 'qris' => 'QRIS', 'tunai' => 'Tunai', 'lainnya' => 'Lainnya']" :value="$pesanan->metode_pembayaran" placeholder="Belum dicatat" />
                <x-admin.formulir.masukan
                    name="paket_opsional"
                    label="Catatan tambahan (opsional)"
                    :value="$pesanan->paket_opsional"
                    placeholder="Layanan di luar paket, tidak menambah harga otomatis. Contoh: tambahan makan"
                />
                <x-admin.formulir.area-teks name="permintaan_khusus" label="Permintaan Khusus" :value="$pesanan->permintaan_khusus" class="sm:col-span-2 xl:col-span-4" />
            </div>
        </x-admin.kartu>

        @error('konfirmasi_tamu')
            <x-admin.peringatan type="warning">{{ $message }}</x-admin.peringatan>
        @enderror

        <input type="hidden" name="konfirmasi_tamu" :value="dikonfirmasi ? 1 : 0">

        <div class="flex justify-end gap-3">
            <x-admin.tombol as="a" href="{{ route('admin.pesanan.detail', $pesanan) }}" variant="secondary">Batal</x-admin.tombol>
            <x-admin.tombol type="submit">Simpan Perubahan</x-admin.tombol>
        </div>

        <x-admin.modal nama="konfirmasi-perubahan-data" size="lg">
            <x-slot:header>
                <p class="font-semibold text-ink">Konfirmasi perubahan data tamu</p>
            </x-slot:header>

            <div class="space-y-4 text-sm">
                <p class="text-muted">Data berikut akan berubah. Perubahan harus dikonfirmasi ke tamu <strong class="text-ink">{{ $pesanan->nama_lengkap }}</strong> agar tidak terjadi salah paham.</p>
                <ul class="divide-y divide-surface-border rounded-lg border border-surface-border">
                    <template x-for="baris in perubahan" :key="baris.label">
                        <li class="px-3.5 py-2.5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted" x-text="baris.label"></p>
                            <p class="mt-0.5 text-ink"><span class="text-muted line-through" x-text="baris.lama"></span> <span aria-hidden="true">→</span> <strong x-text="baris.baru"></strong></p>
                        </li>
                    </template>
                </ul>
                <label class="flex cursor-pointer items-start gap-3 rounded-lg bg-page p-3.5 text-ink">
                    <input type="checkbox" x-model="dikonfirmasi" class="mt-0.5 rounded border-surface-border text-hijau-aksi focus:ring-hijau-aksi">
                    <span>Saya sudah mengonfirmasi perubahan ini ke tamu dan tamu menyetujuinya.</span>
                </label>
                <p class="text-xs text-muted">Setelah disimpan, pesan WhatsApp konfirmasi perubahan siap dikirim dari kartu Pesan WhatsApp di halaman pesanan.</p>
            </div>

            <x-slot:footer>
                <x-admin.tombol type="button" variant="secondary" x-on:click="tutup()">Periksa lagi</x-admin.tombol>
                <x-admin.tombol type="button" x-bind:disabled="! dikonfirmasi" x-bind:class="! dikonfirmasi && 'opacity-50 cursor-not-allowed'" x-on:click="kirim()">Ya, simpan perubahan</x-admin.tombol>
            </x-slot:footer>
        </x-admin.modal>
    </form>
</x-tata-letak.admin>
