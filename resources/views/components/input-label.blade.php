@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-600']) }}>
    {{ $value ?? $slot }}
</label>
