<div>
    <x-ui.page-header :eyebrow="__('admin.users.eyebrow')" :title="__('admin.users.title')" :lede="__('admin.users.lede')">
        <x-slot name="actions">
            <x-ui.button variant="ghost" :href="route('admin.roles.index')">{{ __('roles.manage_link') }}</x-ui.button>
            <x-ui.search-input :placeholder="__('admin.users.search_placeholder')" class="w-64" />
            <x-ui.button variant="primary" type="button" wire:click="$dispatch('create-user')">{{ __('admin.users.add_user') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.table>
        <x-slot name="head">
            <x-ui.th sortable field="name" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.users.name') }}</x-ui.th>
            <x-ui.th sortable field="email" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.users.email') }}</x-ui.th>
            <x-ui.th>{{ __('admin.users.role') }}</x-ui.th>
            <x-ui.th>{{ __('admin.users.branch') }}</x-ui.th>
            <x-ui.th sortable field="is_active" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.users.status') }}</x-ui.th>
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
                    <x-ui.icon-button type="button" wire:click="$dispatch('edit-user', { userId: {{ $user->id }} })" :title="__('admin.users.edit')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </x-ui.icon-button>
                </x-ui.td>
            </x-ui.tr>
        @empty
            <tr><td colspan="6" class="p-0"><x-ui.empty :title="__('admin.users.no_users_yet')" /></td></tr>
        @endforelse
    </x-ui.table>

    <div class="mt-5">{{ $users->links() }}</div>

    @livewire('admin.user-management.user-form')
</div>
