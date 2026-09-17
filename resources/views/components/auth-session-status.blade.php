@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-sm border-s-4 border-success bg-success/5 px-4 py-3 text-xs font-medium leading-relaxed text-success']) }}>
        {{ $status }}
    </div>
@endif
