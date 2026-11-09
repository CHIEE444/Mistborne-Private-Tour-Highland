{{-- Tautan WhatsApp admin (nomor dari Pengaturan), selalu target _blank + rel noopener noreferrer. --}}
@props(['text' => null])

@php
    $href = 'https://wa.me/'.\App\Models\Pengaturan::current()->nomor_whatsapp_admin . ($text ? '?text=' . urlencode($text) : '');
@endphp

<a {{ $attributes->merge([
        'href' => $href,
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
        'data-catat-wa' => $text ?: 'Menekan tombol WhatsApp (tanpa pesan awal)',
    ]) }}>{{ $slot }}</a>