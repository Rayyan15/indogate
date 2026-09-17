<div>
    <x-modal name="user-form" max-width="md">
        <form wire:submit="save" class="space-y-5 p-6">
            <div class="border-b border-neutral-200 pb-4">
                <x-ui.eyebrow>{{ __('admin.users.eyebrow') }}</x-ui.eyebrow>
                <h2 class="mt-1 font-display text-xl text-neutral-900">{{ $userId ? __('admin.users.form_edit_title') : __('admin.users.form_create_title') }}</h2>
            </div>

            <x-ui.field :label="__('admin.users.name')" :error="$errors->first('name')">
                <input type="text" wire:model="name" class="admin-input">
            </x-ui.field>

            <x-ui.field :label="__('admin.users.email')" :error="$errors->first('email')">
                <input type="email" wire:model="email" class="admin-input">
            </x-ui.field>

            <x-ui.field :label="__('admin.users.password') . ($userId ? ' ' . __('admin.users.password_hint') : '')" :error="$errors->first('password')">
                <input type="password" wire:model="password" class="admin-input">
            </x-ui.field>

            <x-ui.field :label="__('admin.users.branch')" :error="$errors->first('branch_id')">
                <select wire:model="branch_id" class="admin-input">
                    <option value="">{{ __('admin.users.branch_select') }}</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field :label="__('admin.users.role')" :error="$errors->first('role')">
                <select wire:model="role" class="admin-input">
                    <option value="">{{ __('admin.users.role_select') }}</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r->name }}">{{ $r->name }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <label class="flex items-center gap-2 text-sm text-neutral-700">
                <input type="checkbox" wire:model="is_active" class="rounded accent-red-600">
                {{ __('admin.users.is_active') }}
            </label>
            @error('is_active') <p class="text-xs text-danger">{{ $message }}</p> @enderror

            <div class="flex justify-end gap-3 border-t border-neutral-200 pt-5">
                <x-ui.button variant="secondary" type="button" x-on:click="$dispatch('close-modal', 'user-form')">{{ __('admin.users.cancel') }}</x-ui.button>
                <x-ui.button variant="primary" type="submit" wire:loading.attr="disabled">{{ __('admin.users.save') }}</x-ui.button>
            </div>
        </form>
    </x-modal>
</div>
