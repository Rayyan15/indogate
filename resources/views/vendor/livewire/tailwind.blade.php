@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between gap-4">
            {{-- Mobile Previous / Next Buttons --}}
            <div class="flex justify-between flex-1 sm:hidden">
                <span>
                    @if ($paginator->onFirstPage())
                        <span class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-neutral-400 bg-neutral-100 border border-neutral-200 cursor-not-allowed rounded-md">
                            &laquo; {{ __('pagination.previous') }}
                        </span>
                    @else
                        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 transition">
                            &laquo; {{ __('pagination.previous') }}
                        </button>
                    @endif
                </span>

                <span>
                    @if ($paginator->hasMorePages())
                        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 transition">
                            {{ __('pagination.next') }} &raquo;
                        </button>
                    @else
                        <span class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-neutral-400 bg-neutral-100 border border-neutral-200 cursor-not-allowed rounded-md">
                            {{ __('pagination.next') }} &raquo;
                        </span>
                    @endif
                </span>
            </div>

            {{-- Desktop Pagination --}}
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs text-neutral-600">
                        <span>Menampilkan</span>
                        <span class="font-bold text-neutral-900 font-mono">{{ $paginator->firstItem() }}</span>
                        <span>sampai</span>
                        <span class="font-bold text-neutral-900 font-mono">{{ $paginator->lastItem() }}</span>
                        <span>dari</span>
                        <span class="font-bold text-neutral-900 font-mono">{{ $paginator->total() }}</span>
                        <span>data</span>
                    </p>
                </div>

                <div>
                    <span class="relative z-0 inline-flex rtl:flex-row-reverse rounded-md shadow-2xs overflow-hidden border border-neutral-200">
                        {{-- Previous Page Link --}}
                        <span>
                            @if ($paginator->onFirstPage())
                                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                                    <span class="relative inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-neutral-300 bg-neutral-50 border-e border-neutral-200 cursor-not-allowed h-8" aria-hidden="true">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </span>
                                </span>
                            @else
                                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="relative inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-neutral-600 bg-white border-e border-neutral-200 h-8 hover:bg-neutral-100 hover:text-neutral-900 transition" aria-label="{{ __('pagination.previous') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                            @endif
                        </span>

                        {{-- Pagination Elements --}}
                        @foreach ($elements as $element)
                            {{-- "Three Dots" Separator --}}
                            @if (is_string($element))
                                <span aria-disabled="true">
                                    <span class="relative inline-flex items-center px-3 py-1.5 text-xs font-mono text-neutral-400 bg-white border-e border-neutral-200 h-8 cursor-default select-none">{{ $element }}</span>
                                </span>
                            @endif

                            {{-- Array Of Links --}}
                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                        @if ($page == $paginator->currentPage())
                                            <span aria-current="page">
                                                <span class="relative inline-flex items-center px-3.5 py-1.5 text-xs font-bold font-mono text-white bg-neutral-900 border-e border-neutral-900 h-8 z-10">{{ $page }}</span>
                                            </span>
                                        @else
                                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="relative inline-flex items-center px-3.5 py-1.5 text-xs font-medium font-mono text-neutral-700 bg-white border-e border-neutral-200 h-8 hover:bg-neutral-100 hover:text-neutral-950 transition">
                                                {{ $page }}
                                            </button>
                                        @endif
                                    </span>
                                @endforeach
                            @endif
                        @endforeach

                        {{-- Next Page Link --}}
                        <span>
                            @if ($paginator->hasMorePages())
                                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="relative inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-neutral-600 bg-white h-8 hover:bg-neutral-100 hover:text-neutral-900 transition" aria-label="{{ __('pagination.next') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            @else
                                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                                    <span class="relative inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-neutral-300 bg-neutral-50 cursor-not-allowed h-8" aria-hidden="true">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </span>
                                </span>
                            @endif
                        </span>
                    </span>
                </div>
            </div>
        </nav>
    @endif
</div>
