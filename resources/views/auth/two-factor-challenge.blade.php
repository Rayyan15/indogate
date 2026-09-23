<x-guest-layout>
    <div class="mb-6 border-b border-neutral-200 pb-5">
        <x-ui.eyebrow>Security check</x-ui.eyebrow>
        <h1 class="mt-1 font-display text-2xl text-neutral-900">{{ __('Two-factor authentication') }}</h1>
        <p class="mt-2 text-sm text-neutral-500">{{ __('Enter the code from your authenticator app, or one of your recovery codes.') }}</p>
    </div>

    <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="code" :value="__('Code')" />
            <x-text-input id="code" class="mt-1.5 block w-full" type="text" name="code" inputmode="numeric" autofocus autocomplete="one-time-code" />
            <x-input-error :messages="$errors->get('code')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="recovery_code" :value="__('Recovery code')" />
            <x-text-input id="recovery_code" class="mt-1.5 block w-full" type="text" name="recovery_code" autocomplete="one-time-code" />
            <x-input-error :messages="$errors->get('recovery_code')" class="mt-1.5" />
        </div>

        <div class="flex justify-end border-t border-neutral-200 pt-5">
            <x-primary-button>{{ __('Log in') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
