<x-tata-letak.admin title="Input Pesanan Kustom" group="{{ __('admin.sidebar.grup_operasional') }}">
    <x-admin.kepala-halaman title="Input Pesanan Kustom" subtitle="Catat permintaan yang diterima admin lewat WhatsApp, telepon, atau kanal lain.">
        <x-slot:actions>
            <x-admin.tombol as="a" href="{{ route('admin.pesanan-kustom.indeks') }}" variant="secondary">Kembali ke Pesanan Kustom</x-admin.tombol>
        </x-slot:actions>
    </x-admin.kepala-halaman>

    <form method="POST" action="{{ route('admin.pesanan-kustom.simpan') }}" class="space-y-6" x-data="{ tanggalFleksibel: {{ old('tanggal_fleksibel') ? 'true' : 'false' }} }">
        @csrf

        <x-admin.kartu>
            <h2 class="mb-4 text-sm font-semibold text-ink">Data Pelanggan & Trip</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.formulir.masukan name="nama" label="Nama Pelanggan" placeholder="Nama orang yang menjadi kontak utama pesanan" wajib />
                <x-admin.formulir.masukan name="whatsapp" label="Nomor WhatsApp" placeholder="Diawali 08 atau +62, contoh 081234567890" inputmode="tel" pattern="(08[0-9]{7,12}|628[0-9]{7,12})" maxlength="15" autocomplete="tel" wajib />
                <x-admin.formulir.masukan name="destinasi_diinginkan" label="Destinasi" placeholder="Tempat atau kawasan tujuan tamu; rute detail menyusul. Contoh: Dieng dan Bromo" wajib class="sm:col-span-2" />

                <div class="max-w-sm">
                    <x-admin.formulir.kolom label="Tanggal Perjalanan (belum pasti? centang fleksibel)" for="tanggal_diinginkan" name="tanggal_diinginkan">
                        <input
                            type="date"
                            id="tanggal_diinginkan"
                            name="tanggal_diinginkan"
                            value="{{ old('tanggal_diinginkan') }}"
                            min="{{ now()->addDays(7)->toDateString() }}"
                            :required="!tanggalFleksibel"
                            :disabled="tanggalFleksibel"
                            class="w-full rounded-lg border border-surface-border bg-white px-3.5 py-2.5 text-sm text-ink focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi disabled:bg-page"
                        />
                    </x-admin.formulir.kolom>
                    <label class="mt-2 inline-flex items-center gap-2 text-sm text-muted">
                        <input type="checkbox" name="tanggal_fleksibel" value="1" x-model="tanggalFleksibel" @checked(old('tanggal_fleksibel')) class="rounded border-surface-border text-hijau-aksi focus:ring-hijau-aksi" />
                        Tanggal masih fleksibel
                    </label>
                    @error('tanggal_diinginkan') <x-admin.formulir.galat :pesan="$message" /> @enderror
                </div>

                <x-admin.formulir.masukan name="durasi_hari" type="number" label="Durasi (hari)" min="1" max="30" placeholder="Jumlah hari, bukan malam. Contoh: 3" wajib />
                <x-admin.formulir.masukan name="jumlah_orang" type="number" label="Jumlah Orang" min="1" max="50" placeholder="Total peserta termasuk anak-anak" wajib />
                <x-admin.formulir.masukan name="lokasi_penjemputan" label="Area Penjemputan" placeholder="Kota, hotel, atau titik temu awal. Contoh: Wonosobo atau Malang" wajib class="sm:col-span-2" />
                <fieldset class="sm:col-span-2">
                    <legend class="mb-2 text-sm font-medium text-ink">Hal yang paling disukai (opsional)</legend>
                    <p class="mb-2 text-xs text-muted">Pilih minat yang tamu sebutkan untuk membantu menyusun rute.</p>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach (['alam' => 'Alam', 'budaya' => 'Budaya', 'kuliner' => 'Kuliner', 'fotografi' => 'Fotografi', 'keluarga' => 'Ramah keluarga', 'petualangan' => 'Petualangan', 'sejarah' => 'Sejarah', 'belanja' => 'Belanja'] as $nilai => $label)
                            <label class="inline-flex items-center gap-2 text-sm text-muted"><input type="checkbox" name="minat_perjalanan[]" value="{{ $nilai }}" @checked(in_array($nilai, (array) old('minat_perjalanan', []), true)) />{{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <x-admin.formulir.pilihan name="tempo_perjalanan" label="Suasana Perjalanan" :pilihan="['' => 'Belum ditentukan', 'santai' => 'Santai (banyak waktu di tiap tempat)', 'normal' => 'Seimbang', 'aktif' => 'Padat (ingin banyak tempat)']" />
                <x-admin.formulir.area-teks name="catatan" label="Gambaran Trip yang Diinginkan (opsional)" placeholder="Gambaran kasar tamu: pengalaman yang dicari, tempat wajib, aktivitas, dan hal yang ingin dihindari" rows="4" class="sm:col-span-2" />
            </div>
        </x-admin.kartu>

        <div class="flex justify-end gap-3">
            <x-admin.tombol as="a" href="{{ route('admin.pesanan-kustom.indeks') }}" variant="secondary">Batal</x-admin.tombol>
            <x-admin.tombol type="submit">Simpan Pesanan Kustom</x-admin.tombol>
        </div>
    </form>
</x-tata-letak.admin>
