<form action="{{ route('public.currency.switch', ['locale' => app()->getLocale()]) }}" method="POST" class="inline-flex" aria-label="{{ __('customer.currency.label') }}">
    @csrf
    <div class="flex items-center rounded-full border border-neutral-200 bg-neutral-0 p-0.5 text-xs font-semibold text-neutral-500">
        @foreach(\App\Support\Storefront\StorefrontCurrency::supported() as $curr)
            <button type="submit" name="currency" value="{{ $curr }}"
                class="rounded-full px-2.5 py-1 transition-colors {{ \App\Support\Storefront\StorefrontCurrency::current() === $curr ? 'bg-neutral-900 text-neutral-0' : 'hover:text-neutral-900' }}">{{ $curr }}</button>
        @endforeach
    </div>
</form>
