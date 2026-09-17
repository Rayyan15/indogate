<div>
    <x-ui.page-header :eyebrow="__('admin.users.eyebrow')" :title="__('admin.users.title')" :lede="__('admin.users.lede')">
        <x-slot name="actions">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('admin.users.search_placeholder') }}" class="admin-input w-64">
            <x-ui.button variant="primary" type="button" wire:click="$dispatch('create-user')">{{ __('admin.users.add_user') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.table>
        <x-slot name="head">
            <x-ui.th>{{ __('admin.users.name') }}</x-ui.th>
            <x-ui.th>{{ __('admin.users.email') }}</x-ui.th>
            <x-ui.th>{{ __('admin.users.role') }}</x-ui.th>
            <x-ui.th>{{ __('admin.users.branch') }}</x-ui.th>
            <x-ui.th>{{ __('admin.users.status') }}</x-ui.th>
            <x-ui.th numeric>{{ __('admin.users.actions') }}</x-ui.th>
        </x-slot>
        @forelse ($users as $user)
            <x-ui.tr wire:key="user-{{ $user->id }}">
                <x-ui.td class="font-medium text-neutral-900">{{ $user->name }}</x-ui.td>
                <x-ui.td class="font-mono text-neutral-500">{{ $user->email }}</x-ui.td>
                <x-ui.td>{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</x-ui.td>
                <x-ui.td>{{ $user->branch?->name ?? '—' }}</x-ui.td>
                <x-ui.td><x-ui.status :status="$user->is_active ? 'paid' : 'cancelled'">{{ $user->is_active ? __('admin.users.active') : __('admin.users.inactive') }}</x-ui.status></x-ui.td>
                <x-ui.td numeric>
                    <x-ui.button variant="ghost" type="button" wire:click="$dispatch('edit-user', { userId: {{ $user->id }} })">{{ __('admin.users.edit') }}</x-ui.button>
                </x-ui.td>
            </x-ui.tr>
        @empty
            <tr><td colspan="6" class="p-0"><x-ui.empty :title="__('admin.users.no_users_yet')" /></td></tr>
        @endforelse
    </x-ui.table>

    <div class="mt-5">{{ $users->links() }}</div>

    @livewire('admin.user-management.user-form')
</div>
