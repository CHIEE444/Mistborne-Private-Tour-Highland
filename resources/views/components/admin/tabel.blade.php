@props(['kolom' => [], 'lebarKolom' => [], 'layout' => 'auto'])

<div {{ $attributes->class(['overflow-x-auto rounded-admin-kartu border border-surface-border bg-white']) }}>
    <table @class([
        'w-full text-start text-sm',
        'table-fixed min-w-[830px]' => $layout === 'fixed',
        'min-w-[640px]' => $layout !== 'fixed',
    ])>
        @if (count($lebarKolom) === count($kolom))
            <colgroup>
                @foreach ($lebarKolom as $lebar)
                    <col style="width: {{ $lebar }}" />
                @endforeach
            </colgroup>
        @endif
        @if (count($kolom) > 0)
            <thead>
                <tr class="border-b border-surface-border text-xs font-semibold uppercase tracking-wide text-muted">
                    @foreach ($kolom as $judul)
                        <th scope="col" class="px-4 py-3 text-start">{{ $judul }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif

        <tbody class="divide-y divide-surface-border">
            {{ $slot }}
        </tbody>
    </table>
</div>
