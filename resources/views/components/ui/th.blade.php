{{-- Pass :sortable="true" :field="'column'" :sort-field="$sortField" :sort-direction="$sortDirection"
     to turn a header into a click-to-sort button. Component must use
     App\Livewire\Concerns\Sortable and expose sortBy(). --}}
@props(['numeric' => false, 'sortable' => false, 'field' => null, 'sortField' => null, 'sortDirection' => 'asc'])
<th scope="col" {{ $attributes->merge(['class' => 'px-4 py-3 first:ps-5 last:pe-5 ' . ($numeric ? 'text-end' : 'text-start')]) }}>
    @if($sortable && $field)
        <button type="button" wire:click="sortBy('{{ $field }}')" class="inline-flex items-center gap-1 {{ $sortField === $field ? 'text-red-600' : 'hover:text-neutral-900' }}">
            {{ $slot }}
            @if($sortField === $field)
                @if($sortDirection === 'asc')
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"></path></svg>
                @else
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                @endif
            @else
                <svg class="h-3.5 w-3.5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
            @endif
        </button>
    @else
        {{ $slot }}
    @endif
</th>
