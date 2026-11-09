<x-tata-letak.admin title="Pesanan Baru (manual)" group="{{ __('admin.sidebar.grup_operasional') }}">
    <x-admin.kepala-halaman title="Pesanan Baru (manual)" subtitle="Untuk tamu yang deal langsung di WhatsApp/tanpa form publik" />

    <form
        method="POST"
        action="{{ route('admin.pesanan.simpan') }}"
        class="space-y-6"
        x-data="{
            journeyId: @js((string) old('perjalanan_id', '')),
            tanggalTrip: @js(old('tanggal_trip')),
            jadwal: @js($pilihanTanggal),
            durasi: @js($daftarJourney->pluck('durasi_hari', 'id')),
            rentang() {
                const hari = parseInt(this.durasi[this.journeyId] || 0, 10);
                if (! hari) return '';
                const label = hari > 1 ? hari + ' hari ' + (hari - 1) + ' malam' : '1 hari';
                if (! this.tanggalTrip) return 'Durasi ' + label;
                const akhir = new Date(this.tanggalTrip + 'T00:00:00');
                akhir.setDate(akhir.getDate() + hari - 1);
                const format = (t) => t.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                return 'Durasi ' + label + ' · ' + format(new Date(this.tanggalTrip + 'T00:00:00')) + (hari > 1 ? ' s.d. ' + format(akhir) : '');
            },
            pilihJourney(id) { this.journeyId = id; this.tanggalTrip = ''; },
            jadwalTersedia() { return this.jadwal[this.journeyId] ?? []; },
        }"
    >
        @csrf

        <x-admin.kartu>
            <h2 class="text-sm font-semibold text-ink">Perjalanan</h2>
            <p class="mb-4 text-xs text-muted">Pilih journey, lalu salah satu Tanggal Tersedia-nya (minimal H-7). Sisa slot sudah memperhitungkan kuota journey dan guide yang masih bebas.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.formulir.pilihan
                    name="perjalanan_id"
                    label="Journey"
                    wajib
                    placeholder="Pilih journey"
                    :pilihan="$daftarJourney->pluck('nama', 'id')"
                    x-on:change="pilihJourney($event.target.value)"
                />

                <div>
                    <x-admin.formulir.label for="tanggal_trip" wajib>Tanggal Trip</x-admin.formulir.label>
                    <select
                        id="tanggal_trip"
                        name="tanggal_trip"
                        required
                        aria-required="true"
                        x-model="tanggalTrip"
                        :disabled="! journeyId"
                        class="w-full rounded-lg border border-surface-border bg-white px-3.5 py-2.5 text-sm text-ink focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi"
                    >
                        <option value="" x-text="! journeyId ? 'Pilih journey dahulu' : (jadwalTersedia().some((opsi) => ! opsi.penuh) ? 'Pilih tanggal yang tersedia' : 'Belum ada tanggal tersedia, tambahkan dulu')">Pilih journey dahulu</option>
                        <template x-for="opsi in jadwalTersedia()" :key="opsi.tanggal">
                            <option :value="opsi.tanggal" :disabled="opsi.penuh" x-text="opsi.label"></option>
                        </template>
                    </select>
                    {{-- Tanggal yang diminta tamu belum dibuka? Buka Tanggal Tersedia journey ini di tab baru, tambahkan, lalu muat ulang halaman ini. --}}
                    <a x-show="journeyId" x-cloak :href="@js(route('admin.perjalanan.tanggal.indeks', '__ID__')).replace('__ID__', journeyId)" target="_blank" rel="noopener" class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-hijau-aksi hover:underline">
                        <span class="material-symbols-outlined text-[16px]!" aria-hidden="true">add</span>Tambah tanggal untuk journey ini
                    </a>
                    <p class="mt-1 text-xs font-medium text-admin-sidebar" x-show="rentang()" x-text="rentang()" x-cloak></p>
                    @error('tanggal_trip')
                        <x-admin.formulir.galat :pesan="$message" />
                    @enderror
                </div>

                <x-admin.formulir.uang name="harga" label="Harga (kosongkan = pakai harga journey)" nullable placeholder="Kosongkan = harga default journey" />
                <div class="rounded-lg border border-surface-border bg-page px-4 py-3 sm:col-span-2">
                    <p class="text-sm font-medium text-ink">Status awal: Dihubungi</p>
                    <p class="mt-1 text-xs leading-5 text-muted">Pesanan baru masuk sebagai Dihubungi. Setelah tamu transfer dan diverifikasi, catat pembayarannya dari detail pesanan.</p>
                </div>
            </div>
        </x-admin.kartu>

        <x-admin.kartu>
            <h2 class="text-sm font-semibold text-ink">Data Tamu</h2>
            <p class="mb-4 text-xs text-muted">Kontak dan detail logistik tamu — nomor WhatsApp dipakai untuk semua pesan otomatis berikutnya.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.formulir.masukan name="nama_lengkap" label="Nama Lengkap" wajib />
                <x-admin.formulir.masukan name="whatsapp" label="Nomor WhatsApp" placeholder="Diawali 08 atau +62, contoh 081234567890" wajib />
                <x-admin.formulir.masukan name="jumlah_wisatawan" type="number" label="Jumlah Wisatawan" min="1" max="50" step="1" inputmode="numeric" required placeholder="Angka bulat 1–50 orang" wajib />
                <x-admin.formulir.penginapan class="sm:col-span-2" />
                <x-admin.formulir.pilihan
                    name="kendaraan_pilihan"
                    label="Mobil Destinasi"
                    :pilihan="\App\Bantuan\PilihanKendaraan::pilihanAdmin()"
                    placeholder="Belum ditentukan. Terisi otomatis dari destinasi saat tamu booking. Jumlah tamu saja; guide yang merangkap driver memakai 1 kursi."
                    class="sm:col-span-2"
                />
                <x-admin.formulir.masukan name="lokasi_pickup" label="Alamat / titik jemput" placeholder="Nama hotel, alamat, atau patokan" wajib />
                <x-admin.formulir.masukan name="waktu_pickup" type="time" label="Jam jemput (WIB, opsional)" />
                <x-admin.formulir.masukan
                    name="paket_opsional"
                    label="Catatan tambahan (opsional)"
                    placeholder="Layanan di luar paket, tidak menambah harga otomatis. Contoh: tambahan makan"
                    class="sm:col-span-2"
                />
                <x-admin.formulir.area-teks name="permintaan_khusus" label="Permintaan Khusus" class="sm:col-span-2" />
            </div>
        </x-admin.kartu>

        <x-admin.kartu>
            <h2 class="text-sm font-semibold text-ink">Pembayaran</h2>
            <p class="mb-4 text-xs text-muted">Catat pembayaran yang sudah diterima. Sisa tagihan dihitung dari harga dikurangi jumlah dibayar.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.formulir.uang name="nominal_dibayar" label="Sudah dibayar" nullable />
                <x-admin.formulir.pilihan
                    name="metode_pembayaran"
                    label="Metode pembayaran"
                    :pilihan="['transfer' => 'Transfer bank', 'qris' => 'QRIS', 'tunai' => 'Tunai', 'lainnya' => 'Lainnya']"
                    placeholder="Belum dicatat"
                />
                <p class="text-xs leading-5 text-muted sm:col-span-2">Tenggat pembayaran mulai dihitung saat pesanan dikonfirmasi, mengikuti pengaturan batas pembayaran.</p>
            </div>
        </x-admin.kartu>

        <div class="flex justify-end gap-3">
            <x-admin.tombol as="a" href="{{ route('admin.pesanan.indeks') }}" variant="secondary">Batal</x-admin.tombol>
            <x-admin.tombol type="submit">Buat Pesanan</x-admin.tombol>
        </div>
    </form>
</x-tata-letak.admin>
