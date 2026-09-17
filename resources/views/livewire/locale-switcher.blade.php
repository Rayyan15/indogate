<div x-data="{ open: false }" class="relative">
    <button type="button" x-on:click="open = !open" x-on:click.outside="open = false" class="text-xs font-medium px-3 py-1.5 rounded-full flex items-center gap-1.5 text-neutral-600 hover:text-neutral-900">
        {{ strtoupper($current) }}
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
    </button>

    <div x-show="open" x-cloak class="absolute end-0 mt-2 w-44 rounded-lg border border-neutral-200 shadow-md z-40 bg-neutral-0">
        @foreach ($locales as $code => $meta)
            <button
                type="button"
                wire:click="switchTo('{{ $code }}')"
                class="w-full text-start px-4 py-2.5 text-sm hover:bg-neutral-50 {{ $code === $current ? 'text-red-600 font-medium' : 'text-neutral-700' }}"
            >
                {{ $meta['native'] }}
            </button>
        @endforeach
    </div>
</div>
