<x-app-layout>
    <x-slot name="header">
        <x-ui.eyebrow>Account</x-ui.eyebrow>
        <h2 class="mt-1 font-display text-2xl text-neutral-900">{{ __('Profile') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <x-ui.panel>
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </x-ui.panel>

            <x-ui.panel>
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </x-ui.panel>

            <x-ui.panel>
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </x-ui.panel>
        </div>
    </div>
</x-app-layout>
