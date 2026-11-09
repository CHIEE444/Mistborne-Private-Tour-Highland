@props(['for' => null, 'wajib' => false])

<label @if ($for) for="{{ $for }}" @endif {{ $attributes->class(['mb-1.5 block text-sm font-medium text-ink']) }}>
    {{ $slot }}

    @if ($wajib)
        <span class="text-status-dibatalkan-teks">*</span>
    @endif
</label>
