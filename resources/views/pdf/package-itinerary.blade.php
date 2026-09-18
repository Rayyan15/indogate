<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $package->getTranslation('name', $locale, false) ?? $package->getTranslation('name', 'en') }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1A1A19; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 20px; border-bottom: 1px solid #E2E2E0; padding-bottom: 4px; }
        .meta { color: #575753; font-size: 11px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { text-align: {{ $locale === 'ar' ? 'right' : 'left' }}; padding: 6px 8px; border-bottom: 1px solid #E2E2E0; font-size: 11px; }
        th { background: #F8F8F7; text-transform: uppercase; letter-spacing: 0.05em; font-size: 9px; }
    </style>
</head>
<body>
    <h1>{{ $package->getTranslation('name', $locale, false) ?? $package->getTranslation('name', 'en') }}</h1>
    @if($package->getTranslation('description', $locale, false))
        <p class="meta">{{ $package->getTranslation('description', $locale, false) }}</p>
    @endif

    @foreach($package->days as $day)
        <h2>{{ __('packaging.pdf.day', ['number' => $day->day_number]) }} — {{ $day->getTranslation('title', $locale, false) }}</h2>
        @if($day->getTranslation('notes', $locale, false))
            <p>{{ $day->getTranslation('notes', $locale, false) }}</p>
        @endif
    @endforeach

    <h2>{{ __('packaging.pdf.components') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('packaging.pdf.component_name') }}</th>
                <th>{{ __('packaging.pdf.day_range') }}</th>
                <th>{{ __('packaging.pdf.qty') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($package->items as $item)
                <tr>
                    <td>{{ $item->inventoryItem->getTranslation('name', $locale, false) }}</td>
                    <td>{{ __('packaging.pdf.day_range_value', ['from' => $item->day_from, 'to' => $item->day_to]) }}</td>
                    <td>{{ $item->qty }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
