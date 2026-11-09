@props(['stat', 'index' => 0])

<div class="stats-item" style="--urutan: {{ $index }}" data-reveal="up" data-reveal-delay="{{ $index * 100 }}">
    <span class="stats-item__rule" aria-hidden="true"></span>
    <dt class="stats-item__label">{{ $stat['label'] }}</dt>
    <dd class="stats-item__value">
        <span class="stats-item__number" aria-hidden="true" data-count-animate data-count-to="{{ $stat['to'] }}" data-decimals="{{ $stat['decimals'] }}">{{ $stat['value'] }}</span>
        @if ($stat['suffix'] !== '')
            <span class="stats-item__suffix" aria-hidden="true">{{ $stat['suffix'] }}</span>
        @endif
        <span class="sr-only">{{ trim($stat['value'].' '.$stat['suffix']) }}</span>
    </dd>
</div>
