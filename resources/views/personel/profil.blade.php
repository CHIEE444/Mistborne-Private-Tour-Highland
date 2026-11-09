<x-tata-letak.personel :personel="$personel">
    <div class="mb-8">
        <p class="text-sm font-medium text-muted">Panel Guide</p>
        <h1 class="mt-1 text-2xl font-semibold text-ink">Profil</h1>
        <p class="mt-2 text-sm text-muted">Data identitas, kontak, dan SIM hanya bisa diubah oleh admin MISTBORNE.</p>
    </div>

    @if (session('sukses'))
        <x-admin.peringatan type="success" class="mb-5">{{ session('sukses') }}</x-admin.peringatan>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <x-admin.kartu>
            <h2 class="mb-4 text-sm font-semibold text-muted">Data Guide</h2>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div><dt class="text-xs text-muted">Nama</dt><dd class="mt-1 text-sm font-medium text-ink">{{ $personel->nama }}</dd></div>
                <div><dt class="text-xs text-muted">Email kontak</dt><dd class="mt-1 text-sm font-medium text-ink">{{ $personel->email ?: '—' }}</dd></div>
                <div><dt class="text-xs text-muted">Email akun (untuk masuk)</dt><dd class="mt-1 text-sm font-medium text-ink">{{ $personel->user?->email ?: '—' }}</dd></div>
                <div><dt class="text-xs text-muted">Nomor WhatsApp</dt><dd class="mt-1 text-sm font-medium text-ink">{{ $personel->whatsapp ?: '—' }}</dd></div>
                <div><dt class="text-xs text-muted">Wilayah operasi</dt><dd class="mt-1 text-sm font-medium text-ink">{{ $personel->wilayah_operasi ?: '—' }}</dd></div>
                <div><dt class="text-xs text-muted">Pengalaman memandu</dt><dd class="mt-1 text-sm font-medium text-ink">{{ $personel->pengalaman_tahun ? $personel->pengalaman_tahun.' tahun' : '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-muted">Keahlian</dt><dd class="mt-1 text-sm font-medium text-ink">{{ $personel->keahlian ?: '—' }}</dd></div>
            </dl>

            <h2 class="mb-4 mt-6 border-t border-surface-border pt-5 text-sm font-semibold text-muted">Kemampuan Mengemudi</h2>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div><dt class="text-xs text-muted">Nomor SIM</dt><dd class="mt-1 text-sm font-medium text-ink">{{ $personel->nomor_sim ?: '—' }}</dd></div>
                <div><dt class="text-xs text-muted">Golongan SIM</dt><dd class="mt-1 text-sm font-medium text-ink">{{ \App\Models\PersonelOperasional::JENIS_SIM[$personel->jenis_sim] ?? '—' }}</dd></div>
                <div>
                    <dt class="text-xs text-muted">SIM berlaku sampai</dt>
                    <dd class="mt-1 text-sm font-medium {{ $personel->masa_berlaku_sim?->isPast() ? 'text-status-dibatalkan-teks' : 'text-ink' }}">{{ $personel->masa_berlaku_sim?->translatedFormat('d M Y') ?: '—' }}@if ($personel->masa_berlaku_sim?->isPast()) (sudah habis, hubungi admin)@endif</dd>
                </div>
                <div><dt class="text-xs text-muted">Pengalaman mengemudi</dt><dd class="mt-1 text-sm font-medium text-ink">{{ $personel->pengalaman_mengemudi_tahun ? $personel->pengalaman_mengemudi_tahun.' tahun' : '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-muted">Kendaraan yang dikuasai</dt><dd class="mt-1 text-sm font-medium text-ink">{{ $personel->kendaraan_dikuasai ?: '—' }}</dd></div>
            </dl>
            <p class="mt-5 text-xs text-muted">Ada data yang keliru atau berubah (nomor WhatsApp, email, SIM)? Hubungi admin MISTBORNE untuk memperbaruinya.</p>
        </x-admin.kartu>

        <x-admin.kartu>
            <h2 class="mb-4 text-sm font-semibold text-muted">Ganti Kata Sandi</h2>
            <form method="POST" action="{{ route('personel.profil.sandi') }}" class="space-y-4">
                @csrf
                @method('PUT')
                @foreach ([['sandi_lama', 'Kata sandi lama', 'Kata sandi yang Anda pakai sekarang', 'current-password'], ['sandi_baru', 'Kata sandi baru', 'Minimal 8 karakter', 'new-password'], ['sandi_baru_confirmation', 'Ulangi kata sandi baru', 'Ketik ulang kata sandi baru', 'new-password']] as [$nama, $label, $placeholder, $autocomplete])
                    <div>
                        <label for="{{ $nama }}" class="mb-1.5 block text-sm font-medium text-ink">{{ $label }}</label>
                        <input type="password" id="{{ $nama }}" name="{{ $nama }}" required autocomplete="{{ $autocomplete }}" placeholder="{{ $placeholder }}"
                            class="w-full rounded-xl border border-surface-border bg-white px-4 py-2.5 text-sm text-ink placeholder:text-muted focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi" />
                        @error($nama)<p class="mt-1.5 text-xs text-status-dibatalkan-teks">{{ $message }}</p>@enderror
                    </div>
                @endforeach
                <div class="flex justify-end"><x-admin.tombol type="submit">Simpan Kata Sandi</x-admin.tombol></div>
            </form>
        </x-admin.kartu>
    </div>
</x-tata-letak.personel>
