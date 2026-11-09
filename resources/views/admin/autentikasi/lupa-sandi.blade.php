<x-tata-letak.autentikasi title="Lupa Sandi">
    <h1 class="text-2xl font-semibold text-ink">{{ __('admin.autentikasi.judul_lupa_sandi') }}</h1>
    <p class="mt-1 text-sm text-muted">{{ __('admin.autentikasi.subjudul_lupa_sandi') }}</p>

    @if (session('status'))
        <x-admin.peringatan type="success" class="mt-6">{{ session('status') }}</x-admin.peringatan>
    @endif

    @if ($errors->any())
        <x-admin.peringatan type="danger" class="mt-6">{{ $errors->first() }}</x-admin.peringatan>
    @endif

    <form method="POST" action="{{ route('admin.autentikasi.kirim-tautan-reset') }}" class="mt-6 space-y-5">
        @csrf

        <x-admin.formulir.masukan name="email" type="email" :label="__('admin.autentikasi.label_email')" wajib autofocus />

        <x-admin.tombol type="submit" class="w-full justify-center" size="lg">
            {{ __('admin.autentikasi.tombol_kirim_tautan') }}
        </x-admin.tombol>
    </form>

    <a href="{{ route('admin.autentikasi.masuk') }}" class="mt-6 flex items-center justify-center gap-1 text-sm font-medium text-muted hover:text-ink">
        <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_back</span>
        {{ __('admin.autentikasi.kembali_ke_masuk') }}
    </a>
</x-tata-letak.autentikasi>
