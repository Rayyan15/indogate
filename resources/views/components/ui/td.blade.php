@props(['numeric' => false, 'sub' => null])
<td {{ $attributes->merge(['class' => 'px-4 py-3.5 align-middle text-center first:ps-5 last:pe-5 ' . ($numeric ? 'font-mono font-semibold tabular-nums text-neutral-900' : '')]) }}>
    {{ $slot }}
    @if($sub)<span class="block font-sans text-[11px] font-normal text-neutral-500">{{ $sub }}</span>@endif
</td>
