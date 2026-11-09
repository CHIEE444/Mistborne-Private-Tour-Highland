@props([
    'as' => 'div',
    'method' => 'GET',
    'action' => null,
])

@php
    $kelas = 'flex flex-col gap-3 rounded-[28px] bg-white p-4 shadow-admin-kartu sm:flex-row sm:flex-wrap sm:items-center';
@endphp

@if ($as === 'form')
    <form method="{{ strtoupper($method) === 'GET' ? 'GET' : 'POST' }}" action="{{ $action }}" {{ $attributes->class([$kelas]) }}>
        @if (strtoupper($method) !== 'GET')
            @csrf
        @endif

        {{ $slot }}
    </form>
@else
    <div {{ $attributes->class([$kelas]) }}>
        {{ $slot }}
    </div>
@endif
