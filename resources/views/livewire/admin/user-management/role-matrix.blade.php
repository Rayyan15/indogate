<div>
    <x-ui.page-header :eyebrow="__('roles.eyebrow')" :title="__('roles.title')" :lede="__('roles.lede')">
        <x-slot name="actions">
            <form wire:submit="createRole" class="flex items-center gap-2">
                <input type="text" wire:model="newRole" maxlength="50" placeholder="{{ __('roles.new_role_placeholder') }}" aria-label="{{ __('roles.new_role') }}"
                       class="w-48 rounded border border-neutral-300 bg-neutral-0 px-3 py-2 text-sm text-neutral-900 focus:border-red-600 focus:ring-red-600">
                <x-ui.button type="submit">{{ __('roles.add_role') }}</x-ui.button>
            </form>
            <x-ui.button variant="primary" type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save">{{ __('roles.save') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if (session()->has('success'))
        <div class="mb-4 rounded border border-success/20 bg-success/10 px-4 py-3 text-sm font-medium text-success">{{ session('success') }}</div>
    @endif
    @foreach (['newRole', 'grants', 'delete'] as $field)
        @error($field)
            <div class="mb-4 rounded border border-danger/20 bg-danger/10 px-4 py-3 text-sm font-medium text-danger">{{ $message }}</div>
        @enderror
    @endforeach

    <div class="overflow-x-auto rounded border border-neutral-200 bg-neutral-0">
        <table class="w-full text-sm">
            <thead class="bg-neutral-50">
                <tr>
                    <th class="sticky start-0 z-10 min-w-[260px] border-b border-neutral-200 bg-neutral-50 px-4 py-3 text-start text-xs font-semibold uppercase tracking-[0.1em] text-neutral-500">{{ __('roles.permission') }}</th>
                    @foreach ($roles as $role)
                        <th class="min-w-[130px] border-b border-s border-neutral-200 px-3 py-3 text-center align-top" wire:key="role-head-{{ $role->id }}">
                            <div class="font-semibold text-neutral-900">{{ $role->name }}</div>
                            <div class="mt-0.5 text-xs font-normal text-neutral-500">{{ __('roles.users_count', ['count' => $role->users_count]) }}</div>
                            @if ($role->name === 'Super Admin')
                                <div class="mt-1 text-[11px] font-normal text-neutral-400">{{ __('roles.locked') }}</div>
                            @elseif (! in_array($role->name, $protected, true) && $role->users_count === 0)
                                <button type="button" wire:click="deleteRole({{ $role->id }})" wire:confirm="{{ __('roles.delete_role') }}: {{ $role->name }}?"
                                        class="mt-1 text-[11px] font-normal text-neutral-400 hover:text-danger">{{ __('roles.delete_role') }}</button>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($groups as $group => $permissions)
                    <tr wire:key="group-{{ $group }}">
                        <td colspan="{{ $roles->count() + 1 }}" class="border-b border-neutral-200 bg-neutral-50/60 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-500">
                            {{ __('roles.groups.'.$group) !== 'roles.groups.'.$group ? __('roles.groups.'.$group) : $group }}
                        </td>
                    </tr>
                    @foreach ($permissions as $permission)
                        @php
                            // Permission names contain dots, so dotted trans keys can't reach them.
                            $meta = (array) (__('roles.perm')[$permission->name] ?? []);
                        @endphp
                        <tr class="hover:bg-neutral-50" wire:key="perm-{{ $permission->id }}">
                            <td class="sticky start-0 z-10 border-b border-neutral-100 bg-neutral-0 px-4 py-3">
                                <div class="font-medium text-neutral-900">{{ $meta['label'] ?? $permission->name }}</div>
                                <div class="mt-0.5 font-mono text-[11px] text-neutral-400">{{ $permission->name }}</div>
                                @isset($meta['description'])
                                    <div class="mt-0.5 text-xs text-neutral-500">{{ $meta['description'] }}</div>
                                @endisset
                            </td>
                            @foreach ($roles as $role)
                                <td class="border-b border-s border-neutral-100 px-3 py-3 text-center" wire:key="cell-{{ $role->id }}-{{ $permission->id }}">
                                    @if ($role->name === 'Super Admin')
                                        <input type="checkbox" checked disabled class="h-4 w-4 rounded border-neutral-300 text-neutral-400">
                                    @else
                                        <input type="checkbox" wire:model="grants.{{ $role->id }}.{{ $permission->id }}"
                                               aria-label="{{ $role->name }} — {{ $permission->name }}"
                                               class="h-4 w-4 cursor-pointer rounded border-neutral-300 text-red-600 focus:ring-red-600">
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>
