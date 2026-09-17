{{-- Status pill with dot. Colour map = PRD §4.6. Label text from slot (lang file). --}}
@props(['status'])
@php
$map = [
    'draft'           => 'border-neutral-200 bg-neutral-100 text-neutral-600',
    'quoted'          => 'border-blue-200 bg-blue-50 text-blue-700',
    'expired'         => 'border-neutral-300 bg-neutral-200 text-neutral-700',
    'confirmed'       => 'border-red-200 bg-red-50 text-red-700',
    'pending_payment' => 'border-warning/20 bg-warning/10 text-warning',
    'pending'         => 'border-warning/20 bg-warning/10 text-warning',
    'partially_paid'  => 'border-warning/20 bg-warning/10 text-warning',
    'paid'            => 'border-success/20 bg-success/10 text-success',
    'verified'        => 'border-success/20 bg-success/10 text-success',
    'in_progress'     => 'border-blue-200 bg-blue-100 text-blue-800',
    'completed'       => 'border-success bg-success text-neutral-0',
    'cancelled'       => 'border-danger/20 bg-danger/10 text-danger',
    'rejected'        => 'border-danger/20 bg-danger/10 text-danger',
];
$cls = $map[$status] ?? $map['draft'];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 whitespace-nowrap rounded border px-2 py-0.5 text-[11px] font-medium $cls"]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $slot }}
</span>
