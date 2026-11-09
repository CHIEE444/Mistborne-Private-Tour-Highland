@php
    $pilihanJabatan = ['Admin Operasional' => 'Admin Operasional', 'Admin' => 'Admin'];
    $adaGalatSandi = $errors->has('password') || $errors->has('password_confirmation');
@endphp
<x-tata-letak.admin title="{{ __('admin.umum.ubah_profil') }}">
    <x-admin.kepala-halaman
        :title="__('admin.umum.ubah_profil')"
        subtitle="Perbarui nama, jabatan, foto, dan kata sandi akun admin"
    />

    {{-- Landscape: kartu identitas (pratinjau langsung) di kiri, formulir di kanan. Satu form untuk keduanya. --}}
    <form
        method="POST"
        action="{{ route('admin.profil.perbarui') }}"
        enctype="multipart/form-data"
        x-data="{
            nama: @js(old('name', $user->name)),
            jabatan: @js(old('jabatan', $user->jabatan)),
            fotoPratinjau: null,
            namaFoto: '',
            sandi: '',
            konfirmasi: '',
            lihatSandi: false,
            pilihFoto(event) {
                const berkas = event.target.files[0];
                this.namaFoto = berkas ? berkas.name : '';
                this.fotoPratinjau = berkas ? URL.createObjectURL(berkas) : null;
            },
        }"
        class="grid items-start gap-6 lg:grid-cols-[minmax(0,20rem)_minmax(0,1fr)]"
    >
        @csrf
        @method('PUT')

        <x-admin.kartu :padding="false" class="overflow-hidden lg:sticky lg:top-6">
            <div class="relative h-28 bg-linear-to-br from-admin-sidebar via-sage-tua to-sage" aria-hidden="true">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_85%_20%,rgba(255,255,255,.18),transparent_45%)]"></div>
            </div>

            <div class="-mt-14 flex flex-col items-center px-6 pb-6 text-center">
                <div class="relative">
                    <div class="rounded-full bg-white p-1 shadow-admin-kartu">
                        <img x-show="fotoPratinjau" x-cloak :src="fotoPratinjau" alt="Pratinjau foto profil baru" class="h-24 w-24 rounded-full object-cover" />
                        <div x-show="!fotoPratinjau"><x-admin.avatar :user="$user" size="xl" /></div>
                    </div>
                    <label for="foto" class="absolute -bottom-1 -end-1 flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border-2 border-white bg-admin-sidebar text-emas shadow transition hover:bg-sage-tua" title="Ganti foto profil">
                        <span class="material-symbols-outlined text-[18px]!" aria-hidden="true">edit</span>
                        <span class="sr-only">Ganti foto profil</span>
                    </label>
                </div>

                <p class="mt-4 text-lg font-semibold wrap-anywhere text-ink" x-text="nama.trim() || @js($user->name)">{{ old('name', $user->name) }}</p>
                <span class="mt-1.5 inline-flex items-center gap-1.5 rounded-full bg-status-completed-bg px-3 py-1 text-xs font-semibold text-status-completed-teks">
                    <span class="material-symbols-outlined text-[14px]!" aria-hidden="true">badge</span>
                    <span x-text="jabatan || 'Admin'">{{ old('jabatan', $user->jabatan) ?: 'Admin' }}</span>
                </span>

                <input id="foto" type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="peer sr-only" x-on:change="pilihFoto($event)" />
                <label for="foto" class="mt-5 inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-full border border-surface-border bg-white px-4 py-2.5 text-sm font-semibold text-ink transition hover:border-sage/50 hover:bg-sage-muda/60 peer-focus-visible:ring-2 peer-focus-visible:ring-hijau-aksi">
                    <span class="material-symbols-outlined text-[18px]!" aria-hidden="true">upload_file</span>
                    <span x-text="namaFoto ? 'Ganti pilihan foto' : 'Pilih foto baru'">Pilih foto baru</span>
                </label>
                <p class="mt-2 min-h-4 text-xs break-all text-muted" x-text="namaFoto ? namaFoto + ' · tersimpan setelah klik Simpan' : 'JPG, PNG, atau WebP · maks. 2 MB'">JPG, PNG, atau WebP · maks. 2 MB</p>
                @error('foto')
                    <x-admin.formulir.galat :pesan="$message" class="mt-1" />
                @enderror
            </div>

            <dl class="divide-y divide-surface-border border-t border-surface-border text-sm">
                <div class="flex items-center gap-3 px-6 py-3">
                    <span class="material-symbols-outlined text-[20px]! text-muted" aria-hidden="true">mail</span>
                    <div class="min-w-0 text-start">
                        <dt class="text-[11px] uppercase tracking-wide text-muted">Email masuk</dt>
                        <dd class="font-medium break-all text-ink">{{ $user->email }}</dd>
                    </div>
                </div>
                <div class="flex items-center gap-3 px-6 py-3">
                    <span class="material-symbols-outlined text-[20px]! text-muted" aria-hidden="true">calendar_month</span>
                    <div class="text-start">
                        <dt class="text-[11px] uppercase tracking-wide text-muted">Akun dibuat</dt>
                        <dd class="font-medium text-ink">{{ $user->created_at?->translatedFormat('d F Y') ?? '—' }}</dd>
                    </div>
                </div>
            </dl>
        </x-admin.kartu>

        <x-admin.kartu :padding="false" class="overflow-hidden">
            <section class="p-5 sm:p-6" aria-labelledby="judul-data-akun">
                <div class="mb-5 flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-status-dihubungi-bg text-status-dihubungi-teks"><span class="material-symbols-outlined" aria-hidden="true">person</span></span>
                    <div>
                        <h2 id="judul-data-akun" class="font-semibold text-ink">Data akun</h2>
                        <p class="mt-0.5 text-xs text-muted">Nama dan jabatan tampil di bilah atas panel dan di riwayat pesanan.</p>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-admin.formulir.masukan name="name" :label="__('admin.profil.label_nama')" :value="$user->name" icon="person" placeholder="Nama lengkap Anda" autocomplete="name" x-model="nama" wajib />
                    <x-admin.formulir.pilihan name="jabatan" :label="__('admin.profil.label_jabatan')" :pilihan="$pilihanJabatan" :value="$user->jabatan" x-model="jabatan" wajib />
                </div>
            </section>

            <section class="border-t border-surface-border bg-page/50 p-5 sm:p-6" aria-labelledby="judul-keamanan">
                <div class="mb-5 flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-status-payment-bg text-status-payment-teks"><span class="material-symbols-outlined" aria-hidden="true">lock</span></span>
                    <div>
                        <h2 id="judul-keamanan" class="font-semibold text-ink">Keamanan</h2>
                        <p class="mt-0.5 text-xs text-muted">Ganti kata sandi hanya bila perlu. Biarkan kosong untuk tetap memakai kata sandi lama.</p>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-admin.formulir.masukan name="password" type="password" :label="__('admin.profil.label_sandi_baru')" icon="lock" placeholder="Kosongkan jika tidak diganti · min. 8 karakter" autocomplete="new-password" x-model="sandi" x-bind:type="lihatSandi ? 'text' : 'password'" />
                    <x-admin.formulir.masukan name="password_confirmation" type="password" :label="__('admin.profil.label_konfirmasi_sandi')" icon="lock" placeholder="Ketik ulang kata sandi baru" autocomplete="new-password" x-model="konfirmasi" x-bind:type="lihatSandi ? 'text' : 'password'" />
                </div>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3 text-xs">
                    <button type="button" x-on:click="lihatSandi = ! lihatSandi" class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 font-semibold text-muted transition hover:bg-white hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-hijau-aksi" :aria-pressed="lihatSandi.toString()">
                        <span class="material-symbols-outlined text-[16px]!" aria-hidden="true" x-text="lihatSandi ? 'visibility_off' : 'visibility'">visibility</span>
                        <span x-text="lihatSandi ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">Tampilkan kata sandi</span>
                    </button>
                    {{-- Cek langsung saat mengetik; aturan yang sama tetap divalidasi server. --}}
                    <p x-cloak x-show="sandi !== '' || konfirmasi !== ''" aria-live="polite" class="flex items-center gap-1.5 font-medium"
                        :class="sandi.length >= 8 && sandi === konfirmasi ? 'text-status-completed-teks' : 'text-amber-800'">
                        <span class="material-symbols-outlined text-[16px]!" aria-hidden="true" x-text="sandi.length >= 8 && sandi === konfirmasi ? 'check_circle' : 'error'">error</span>
                        <span x-text="sandi.length < 8 ? 'Kata sandi minimal 8 karakter (' + sandi.length + '/8)' : (sandi !== konfirmasi ? 'Konfirmasi belum sama' : 'Kata sandi cocok')"></span>
                    </p>
                </div>
                @if ($adaGalatSandi)
                    <div x-data x-init="$el.scrollIntoView({ block: 'center' })"></div>
                @endif
            </section>

            <div class="flex flex-col-reverse gap-3 border-t border-surface-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="text-xs text-muted">Perubahan berlaku setelah Anda menekan Simpan.</p>
                <div class="flex gap-3">
                    <x-admin.tombol as="a" href="{{ route('admin.profil.ubah') }}" variant="secondary" icon="restart_alt">Batalkan</x-admin.tombol>
                    <x-admin.tombol type="submit" icon="save">Simpan perubahan</x-admin.tombol>
                </div>
            </div>
        </x-admin.kartu>
    </form>
</x-tata-letak.admin>
