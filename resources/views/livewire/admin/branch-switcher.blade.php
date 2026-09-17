<div x-data="{ open: false }" class="relative">
    <button type="button" x-on:click="open = !open" x-on:click.outside="open = false" class="inline-flex items-center gap-1.5 rounded border border-neutral-300 bg-neutral-0 px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-100">
        {{ $branches->firstWhere('id', $currentBranchId)?->name ?? 'Pilih Cabang' }}
        <svg class="h-3 w-3 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
    </button>

    <div x-show="open" x-cloak class="absolute end-0 z-40 mt-2 w-44 rounded border border-neutral-200 bg-neutral-0 py-1 shadow-md">
        @foreach ($branches as $branch)
            <button type="button" wire:click="switchTo({{ $branch->id }})"
                class="block w-full px-4 py-2 text-start text-sm {{ $branch->id === $currentBranchId ? 'font-semibold text-red-700' : 'text-neutral-600 hover:bg-neutral-50' }}">
                {{ $branch->name }}
            </button>
        @endforeach
    </div>
</div>
