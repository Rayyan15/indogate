{{-- Ledger table shell. Use x-ui.th / x-ui.tr / x-ui.td inside. --}}
<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded border border-neutral-200 bg-neutral-0']) }}>
    <table class="w-full text-xs text-neutral-700">
        <thead class="border-b border-neutral-200 bg-neutral-50 text-[10px] font-bold uppercase tracking-[0.14em] text-neutral-500">
            <tr>{{ $head }}</tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">{{ $slot }}</tbody>
    </table>
</div>
