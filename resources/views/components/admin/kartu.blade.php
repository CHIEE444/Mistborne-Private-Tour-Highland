@props([
    'padding' => true,
])

<div {{ $attributes->class([
    'rounded-admin-kartu bg-white shadow-admin-kartu',
    'p-5 sm:p-6' => $padding,
]) }}>
    {{ $slot }}
</div>
