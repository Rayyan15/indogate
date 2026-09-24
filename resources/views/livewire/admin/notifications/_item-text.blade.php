@php
    $d = $n->data;
    $p = is_array($d['params'] ?? null) ? $d['params'] : [];
    $dot = ['success' => 'bg-emerald-500', 'warning' => 'bg-amber-500', 'danger' => 'bg-red-600'][$d['severity'] ?? 'info'] ?? 'bg-neutral-400';
@endphp
<span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $n->read_at ? 'bg-neutral-200' : $dot }}"></span>
<span class="min-w-0 flex-1 text-start">
    <span class="block truncate text-sm {{ $n->read_at ? 'text-neutral-600' : 'font-semibold text-neutral-900' }}">{{ __($d['title_key'] ?? '', $p) }}</span>
    <span class="mt-0.5 block text-xs leading-relaxed text-neutral-500">{{ __($d['body_key'] ?? '', $p) }}</span>
    <span class="mt-1 block text-[11px] text-neutral-400">{{ $n->created_at->diffForHumans() }}</span>
</span>
