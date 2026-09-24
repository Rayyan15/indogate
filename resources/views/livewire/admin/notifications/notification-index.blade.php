<div>
    <x-ui.page-header :title="__('notifications.history_title')">
        <x-slot name="actions">
            <x-ui.filter-select model="type" :options="$typeOptions" :placeholder="__('notifications.all_types')" />
            <x-ui.button wire:click="markAllRead">{{ __('notifications.mark_all_read') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if($notifications->isEmpty())
        <x-ui.empty :title="__('notifications.empty')" />
    @else
        <x-ui.panel flush>
            <ul class="divide-y divide-neutral-100">
                @foreach($notifications as $n)
                    <li wire:key="n-{{ $n->id }}" class="flex items-start gap-3 px-5 py-4 {{ $n->read_at ? '' : 'bg-red-50/40' }}">
                        <button type="button" wire:click="open('{{ $n->id }}')" class="flex min-w-0 flex-1 items-start gap-3">
                            @include('livewire.admin.notifications._item-text', ['n' => $n])
                        </button>
                        @unless($n->read_at)
                            <x-ui.button variant="ghost" wire:click="markRead('{{ $n->id }}')">{{ __('notifications.mark_read') }}</x-ui.button>
                        @endunless
                    </li>
                @endforeach
            </ul>
        </x-ui.panel>
        <div class="mt-4">{{ $notifications->links() }}</div>
    @endif
</div>
