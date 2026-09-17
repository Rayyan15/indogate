<x-guest-layout>
    <div class="mb-6 border-b border-neutral-200 pb-5">
        <x-ui.eyebrow>Security check</x-ui.eyebrow>
        <h1 class="mt-1 font-display text-2xl text-neutral-900">Confirm your password</h1>
        <p class="mt-2 text-sm text-neutral-500">{{ __('This is a secure area of the application. Please confirm your password before continuing.') }}</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div class="flex justify-end border-t border-neutral-200 pt-5">
            <x-primary-button>{{ __('Confirm') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
