<x-guest-layout>
    <div class="mb-6 border-b border-neutral-200 pb-5">
        <x-ui.eyebrow>Welcome back</x-ui.eyebrow>
        <h1 class="mt-1 font-display text-2xl text-neutral-900">Sign in</h1>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <label for="remember_me" class="flex items-center gap-2 text-sm text-neutral-600">
            <input id="remember_me" type="checkbox" class="rounded border-neutral-300 accent-red-600" name="remember">
            {{ __('Remember me') }}
        </label>

        <div class="flex items-center justify-between border-t border-neutral-200 pt-5">
            @if (Route::has('password.request'))
                <a class="text-sm text-blue-600 underline-offset-2 hover:text-blue-700 hover:underline" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
            <x-primary-button>{{ __('Log in') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
