<x-guest-layout>
    <div class="mb-6 border-b border-neutral-200 pb-5">
        <x-ui.eyebrow>One more step</x-ui.eyebrow>
        <h1 class="mt-1 font-display text-2xl text-neutral-900">Verify your email</h1>
        <p class="mt-2 text-sm text-neutral-500">{{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <x-auth-session-status class="mb-5" :status="__('A new verification link has been sent to the email address you provided during registration.')" />
    @endif

    <div class="flex items-center justify-between border-t border-neutral-200 pt-5">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>{{ __('Resend Verification Email') }}</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-neutral-500 underline-offset-2 hover:text-neutral-900 hover:underline">{{ __('Log Out') }}</button>
        </form>
    </div>
</x-guest-layout>
