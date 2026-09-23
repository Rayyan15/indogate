<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $booking->code }}</title>
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
    <h1>{{ __('booking.pdf.title') }} — {{ $booking->code }}</h1>
    <p class="meta">
        {{ __('booking.pdf.departure') }}: {{ $booking->departure_date->translatedFormat('d M Y') }}
        @if($booking->return_date) · {{ __('booking.pdf.return') }}: {{ $booking->return_date->translatedFormat('d M Y') }} @endif
        · {{ $booking->currency }} {{ \App\Domain\Finance\Fx::format((int) $booking->total_minor, $booking->currency) }}
    </p>

    <h2>{{ __('booking.pdf.guests') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('booking.pdf.guest_name') }}</th>
                <th>{{ __('booking.pdf.nationality') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($booking->guests as $guest)
                <tr>
                    <td>{{ $guest->name }}{{ $guest->is_lead_guest ? ' ('.__('booking.pdf.lead_guest').')' : '' }}</td>
                    <td>{{ $guest->nationality ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>{{ __('booking.pdf.itinerary') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('booking.pdf.component_name') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($booking->quotation->package->items as $item)
                <tr><td>{{ $item->inventoryItem?->name }}</td></tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
