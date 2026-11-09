<x-tata-letak.autentikasi title="Atur Ulang Sandi">
    <h1 class="text-2xl font-semibold text-ink">{{ __('admin.autentikasi.judul_atur_ulang') }}</h1>

    @if ($errors->any())
        <x-admin.peringatan type="danger" class="mt-6">{{ $errors->first() }}</x-admin.peringatan>
    @endif

    <form method="POST" action="{{ route('admin.autentikasi.simpan-sandi-baru') }}" class="mt-6 space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}" />

        <x-admin.formulir.masukan name="email" type="email" :label="__('admin.autentikasi.label_email')" :value="$email" wajib />
        <x-admin.formulir.masukan name="password" type="password" :label="__('admin.autentikasi.label_sandi_baru')" wajib />
        <x-admin.formulir.masukan name="password_confirmation" type="password" :label="__('admin.autentikasi.label_konfirmasi_sandi')" wajib />

        <x-admin.tombol type="submit" class="w-full justify-center" size="lg">
            {{ __('admin.autentikasi.tombol_simpan_sandi') }}
        </x-admin.tombol>
    </form>
</x-tata-letak.autentikasi>
