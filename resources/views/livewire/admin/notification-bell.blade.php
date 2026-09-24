<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" wire:poll.30s.visible>
    <button type="button" @click="open = !open" class="relative flex items-center rounded p-1.5 text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900" aria-label="{{ __('notifications.bell') }}" :aria-expanded="open">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
        @if($unreadCount > 0)
            <span class="absolute -end-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-none text-white" data-unread-count="{{ $unreadCount }}">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition.opacity class="absolute end-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] rounded border border-neutral-200 bg-white shadow-md">
        <div class="flex items-center justify-between border-b border-neutral-200 px-4 py-3">
            <span class="text-xs font-bold uppercase tracking-[0.12em] text-neutral-900">{{ __('notifications.bell') }}</span>
            @if($unreadCount > 0)
                <button type="button" wire:click="markAllRead" class="text-xs text-neutral-500 underline-offset-4 hover:text-neutral-900 hover:underline">{{ __('notifications.mark_all_read') }}</button>
            @endif
        </div>
        <ul class="max-h-96 divide-y divide-neutral-100 overflow-y-auto">
            @forelse($notifications as $n)
                <li wire:key="bell-{{ $n->id }}">
                    <button type="button" wire:click="open('{{ $n->id }}')" class="flex w-full items-start gap-3 px-4 py-3 transition hover:bg-neutral-50 {{ $n->read_at ? '' : 'bg-red-50/40' }}">
                        @include('livewire.admin.notifications._item-text', ['n' => $n])
                    </button>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-xs text-neutral-500">{{ __('notifications.empty') }}</li>
            @endforelse
        </ul>
        <a href="{{ route('admin.notifications.index') }}" class="block border-t border-neutral-200 px-4 py-2.5 text-center text-xs font-semibold text-neutral-700 hover:bg-neutral-50">{{ __('notifications.view_all') }}</a>
    </div>
</div>
