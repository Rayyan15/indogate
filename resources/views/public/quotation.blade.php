<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('quotation.public.title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-50 text-neutral-900">
    <div class="mx-auto max-w-xl px-4 py-10">
        @if($expired)
            <div class="rounded border border-neutral-300 bg-neutral-100 p-6 text-center">
                <h1 class="font-display text-lg font-medium">{{ __('quotation.public.expired_title') }}</h1>
                <p class="mt-2 text-sm text-neutral-600">{{ __('quotation.public.expired_body') }}</p>
            </div>
        @else
            <h1 class="font-display text-xl font-medium">{{ __('quotation.public.title') }}</h1>
            <p class="mt-1 text-sm text-neutral-600">{{ __('quotation.public.valid_until', ['date' => $quotation->valid_until->translatedFormat('d M Y')]) }}</p>

            <div class="mt-6 divide-y divide-neutral-200 rounded border border-neutral-200 bg-white">
                @foreach($quotation->items as $item)
                    <div class="flex items-center justify-between px-4 py-3 text-sm">
                        <span>{{ is_array($item->description) ? ($item->description[app()->getLocale()] ?? reset($item->description)) : $item->description }} × {{ $item->qty }}</span>
                        <span class="font-mono">{{ $quotation->currency }} {{ \App\Domain\Finance\Fx::format((int) $item->total_minor, $quotation->currency) }}</span>
                    </div>
                @endforeach
                <div class="flex items-center justify-between px-4 py-3 text-sm font-medium">
                    <span>{{ __('quotation.public.total') }}</span>
                    <span class="font-mono">{{ $quotation->currency }} {{ \App\Domain\Finance\Fx::format((int) $quotation->items->sum('total_minor'), $quotation->currency) }}</span>
                </div>
            </div>

            {{-- The customer is the one viewing this page: message the agency, not themselves. --}}
            <a href="https://wa.me/628111111111?text={{ urlencode(__('quotation.public.whatsapp_customer_message', ['link' => url()->current()])) }}" target="_blank"
               class="mt-6 inline-flex w-full items-center justify-center rounded bg-success px-4 py-3 text-sm font-medium text-neutral-0">
                {{ __('quotation.public.whatsapp_button') }}
            </a>
        @endif
    </div>
</body>
</html>
