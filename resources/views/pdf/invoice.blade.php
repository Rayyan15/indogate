<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}" dir="{{ ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('finance.invoice') }} — {{ $booking->code }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1A1A19; line-height: 1.5; margin: 24px; }
        .header { border-bottom: 2px solid #C41230; padding-bottom: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start; }
        .brand { font-size: 22px; font-weight: bold; letter-spacing: 0.08em; color: #1A1A19; }
        .brand span { color: #C41230; }
        .title { font-size: 18px; font-weight: bold; margin-top: 4px; text-transform: uppercase; color: #1A1A19; }
        .subtitle { font-size: 11px; color: #575753; margin-top: 2px; }
        .section-title { font-size: 13px; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #E2E2E0; padding-bottom: 4px; margin-top: 18px; margin-bottom: 8px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { text-align: {{ ($locale ?? app()->getLocale()) === 'ar' ? 'right' : 'left' }}; padding: 8px 10px; border-bottom: 1px solid #E2E2E0; font-size: 11px; }
        th { background: #F8F8F7; text-transform: uppercase; letter-spacing: 0.05em; font-size: 10px; color: #575753; }
        .numeric { text-align: {{ ($locale ?? app()->getLocale()) === 'ar' ? 'left' : 'right' }}; }
        .grid { display: table; width: 100%; margin-top: 10px; }
        .col { display: table-cell; width: 50%; vertical-align: top; padding-inline-end: 15px; }
        .info-row { margin-bottom: 6px; font-size: 11px; }
        .info-label { font-weight: 600; color: #575753; }
        .summary-box { margin-top: 20px; display: table; width: 100%; }
        .summary-notes { display: table-cell; width: 55%; vertical-align: top; font-size: 10px; color: #777; }
        .summary-table { display: table-cell; width: 45%; vertical-align: top; }
        .total-row { font-weight: bold; font-size: 13px; border-top: 2px solid #1A1A19; }
        .badge { display: inline-block; padding: 2px 8px; font-size: 10px; font-weight: bold; border-radius: 4px; background: #EBF3FC; color: #185FA5; }
        .badge-paid { background: #E6F4EA; color: #137333; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">INDO<span>GATE</span></div>
            <div class="subtitle">PT Indo International Gate · {{ $booking->branch?->name }}</div>
            <div class="subtitle">{{ $booking->branch?->address ?? 'Bali & Jakarta, Indonesia' }}</div>
        </div>
        <div style="text-align: {{ ($locale ?? app()->getLocale()) === 'ar' ? 'left' : 'right' }};">
            <div class="title">{{ __('finance.invoice') }}</div>
            <div class="subtitle">{{ __('finance.invoice_number') }}: INV/{{ $booking->branch?->code }}/{{ date('Y') }}/{{ $booking->code }}</div>
            <div class="subtitle">{{ __('finance.date') }}: {{ now()->translatedFormat('d F Y') }}</div>
            <div class="subtitle">{{ __('finance.status') }}: <span class="badge {{ $booking->isFullyPaid() ? 'badge-paid' : '' }}">{{ strtoupper($booking->status->value) }}</span></div>
        </div>
    </div>

    <div class="grid">
        <div class="col">
            <div class="section-title">{{ __('finance.billed_to') }}</div>
            <div class="info-row"><span class="info-label">{{ __('finance.guest_lead') }}:</span> <strong>{{ $booking->quotation?->lead?->name }}</strong></div>
            <div class="info-row"><span class="info-label">{{ __('finance.contact') }}:</span> {{ $booking->quotation?->lead?->phone ?: '—' }}</div>
            <div class="info-row"><span class="info-label">{{ __('finance.nationality') }}:</span> {{ $booking->quotation?->lead?->country ?? '—' }}</div>
        </div>
        <div class="col">
            <div class="section-title">{{ __('finance.booking_details') }}</div>
            <div class="info-row"><span class="info-label">{{ __('finance.booking_code') }}:</span> <strong>{{ $booking->code }}</strong></div>
            <div class="info-row"><span class="info-label">{{ __('finance.departure_date') }}:</span> {{ $booking->departure_date->translatedFormat('d F Y') }}</div>
            @if($booking->return_date)
                <div class="info-row"><span class="info-label">{{ __('finance.return_date') }}:</span> {{ $booking->return_date->translatedFormat('d F Y') }}</div>
            @endif
        </div>
    </div>

    <div class="section-title">{{ __('finance.package_breakdown') }}</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('finance.item_description') }}</th>
                <th class="numeric">{{ __('finance.quantity') }}</th>
                <th class="numeric">{{ __('finance.subtotal') }} ({{ $booking->currency }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($booking->quotation?->items ?? [] as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->name }}</strong>
                        @if($item->description)
                            <div style="font-size: 10px; color: #666;">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="numeric">{{ $item->quantity ?? 1 }}</td>
                    <td class="numeric">{{ number_format($item->total_minor, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td>1</td>
                    <td><strong>{{ __('finance.travel_package') }} — {{ $booking->code }}</strong></td>
                    <td class="numeric">1</td>
                    <td class="numeric">{{ number_format($booking->total_minor, 0, ',', '.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-box">
        <div class="summary-notes">
            <p><strong>{{ __('finance.payment_terms') }}:</strong></p>
            <p>{{ __('finance.terms_note') }}</p>
        </div>
        <div class="summary-table">
            <table>
                <tr>
                    <td>{{ __('finance.total_billed') }}:</td>
                    <td class="numeric"><strong>{{ $booking->currency }} {{ number_format($booking->total_minor, 0, ',', '.') }}</strong></td>
                </tr>
                <tr>
                    <td>{{ __('finance.total_paid') }}:</td>
                    <td class="numeric" style="color: #137333;"><strong>{{ $booking->currency }} {{ number_format($booking->totalPaidMinor(), 0, ',', '.') }}</strong></td>
                </tr>
                <tr class="total-row">
                    <td>{{ __('finance.remaining_balance') }}:</td>
                    <td class="numeric" style="color: {{ $booking->remainingBalanceMinor() > 0 ? '#C41230' : '#137333' }};">
                        {{ $booking->currency }} {{ number_format($booking->remainingBalanceMinor(), 0, ',', '.') }}
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
