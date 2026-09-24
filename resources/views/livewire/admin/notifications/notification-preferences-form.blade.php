<div class="mb-6 space-y-6">
    @if($vapidPublicKey)
        <x-ui.panel>
            <div x-data="staffPush({ publicKey: @js($vapidPublicKey), storeUrl: @js(route('admin.push-subscriptions.store', app()->getLocale())), destroyUrl: @js(route('admin.push-subscriptions.destroy', app()->getLocale())), csrf: @js(csrf_token()) })" x-init="init()" wire:ignore>
                <h3 class="text-sm font-bold text-neutral-900">{{ __('notifications.push.title') }}</h3>
                <p class="mt-1 text-xs text-neutral-500">{{ __('notifications.push.help') }}</p>
                <p x-show="!supported" x-cloak class="mt-3 text-xs text-amber-700">{{ __('notifications.push.unsupported') }}</p>
                <p x-show="denied" x-cloak class="mt-3 text-xs text-red-700">{{ __('notifications.push.denied') }}</p>
                <div class="mt-3" x-show="supported && !denied" x-cloak>
                    <x-ui.button variant="primary" x-show="!subscribed" x-bind:disabled="busy" @click="enable()">{{ __('notifications.push.enable') }}</x-ui.button>
                    <template x-if="subscribed">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-semibold text-emerald-700">{{ __('notifications.push.enabled') }}</span>
                            <x-ui.button x-bind:disabled="busy" @click="disable()">{{ __('notifications.push.disable') }}</x-ui.button>
                        </div>
                    </template>
                </div>
            </div>
        </x-ui.panel>
    @endif

    <x-ui.panel>
        <form wire:submit="save" class="space-y-5">
            <div>
                <h3 class="text-sm font-bold text-neutral-900">{{ __('notifications.preferences.title') }}</h3>
                <p class="mt-1 text-xs text-neutral-500">{{ __('notifications.preferences.help') }}</p>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach($types as $type)
                        <label class="flex items-center gap-2 text-sm text-neutral-700">
                            <input type="checkbox" wire:model="push.{{ $type }}" class="rounded border-neutral-300 text-red-600">
                            {{ __("notifications.$type.title") }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="flex items-center gap-2 text-sm font-semibold text-neutral-900">
                    <input type="checkbox" wire:model="quietEnabled" class="rounded border-neutral-300 text-red-600">
                    {{ __('notifications.preferences.quiet_hours') }}
                </label>
                <p class="mt-1 text-xs text-neutral-500">{{ __('notifications.preferences.quiet_help', ['timezone' => $timezone]) }}</p>
                <div class="mt-3 flex items-center gap-2">
                    <input type="time" wire:model="quietFrom" class="admin-input w-32" aria-label="{{ __('notifications.preferences.quiet_from') }}">
                    <span class="text-xs text-neutral-500">–</span>
                    <input type="time" wire:model="quietTo" class="admin-input w-32" aria-label="{{ __('notifications.preferences.quiet_to') }}">
                </div>
                @error('quietFrom') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                @error('quietTo') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3">
                <x-ui.button type="submit" variant="primary">{{ __('notifications.preferences.save') }}</x-ui.button>
                @if($saved)<span class="text-xs text-emerald-700">{{ __('notifications.preferences.saved') }}</span>@endif
            </div>
        </form>
    </x-ui.panel>
</div>
