@props(['href' => null])

<tr {{ $attributes->class(['transition-colors hover:bg-page']) }}>
    {{ $slot }}
</tr>
