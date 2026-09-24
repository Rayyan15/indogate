<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}" dir="{{ ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('finance.receipt') }} — {{ $payment->booking?->code }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1A1A19; line-height: 1.5; margin: 24px; }
        .header { border-bottom: 2px solid #137333; padding-bottom: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start; }
        .brand { font-size: 22px; font-weight: bold; letter-spacing: 0.08em; color: #1A1A19; }
        .brand span { color: #C41230; }
        .title { font-size: 18px; font-weight: bold; margin-top: 4px; text-transform: uppercase; color: #137333; }
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
        .amount-banner { margin: 20px 0; background: #E6F4EA; border: 1px solid #CEEAD6; border-radius: 6px; padding: 16px; text-align: center; }
        .amount-val { font-size: 24px; font-weight: bold; color: #137333; }
        .footer { margin-top: 40px; display: table; width: 100%; }
        .signature-box { display: table-cell; width: 50%; text-align: center; }
        .signature-line { margin-top: 50px; border-top: 1px solid #1A1A19; width: 180px; margin-left: auto; margin-right: auto; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">INDO<span>GATE</span></div>
            <div class="subtitle">PT Indo International Gate · {{ $payment->branch?->name }}</div>
        </div>
        <div style="text-align: {{ ($locale ?? app()->getLocale()) === 'ar' ? 'left' : 'right' }};">
            <div class="title">{{ __('finance.receipt') }}</div>
            <div class="subtitle">{{ __('finance.receipt_number') }}: RCT/{{ $payment->branch?->code }}/{{ date('Y') }}/{{ $payment->id }}</div>
            <div class="subtitle">{{ __('finance.date') }}: {{ $payment->verified_at ? \App\Support\Branch\CurrentBranch::local($payment->verified_at)->translatedFormat('d F Y') : now()->translatedFormat('d F Y') }}</div>
        </div>
    </div>

    <div class="amount-banner">
        <div style="font-size: 11px; text-transform: uppercase; color: #575753;">{{ __('finance.amount_received') }}</div>
        <div class="amount-val">{{ $payment->currency }} {{ number_format($payment->amount_minor, 0, ',', '.') }}</div>
        @if($payment->currency !== 'IDR')
            <div style="font-size: 11px; color: #575753; margin-top: 4px;">
                (Kurs 1 {{ $payment->currency }} = IDR {{ number_format($payment->fx_rate, 2, ',', '.') }} · Ekuivalen: IDR {{ number_format($payment->idr_equivalent_minor, 0, ',', '.') }})
            </div>
        @endif
    </div>

    <div class="grid">
        <div class="col">
            <div class="section-title">{{ __('finance.received_from') }}</div>
            <div class="info-row"><span class="info-label">{{ __('finance.guest_lead') }}:</span> <strong>{{ $payment->booking?->quotation?->lead?->name }}</strong></div>
            <div class="info-row"><span class="info-label">{{ __('finance.booking_code') }}:</span> <strong>{{ $payment->booking?->code }}</strong></div>
            <div class="info-row"><span class="info-label">{{ __('finance.payment_type') }}:</span> {{ __('finance.type_'.$payment->type) }}</div>
        </div>
        <div class="col">
            <div class="section-title">{{ __('finance.payment_info') }}</div>
            <div class="info-row"><span class="info-label">{{ __('finance.channel') }}:</span> {{ strtoupper($payment->channel) }}</div>
            <div class="info-row"><span class="info-label">{{ __('finance.verified_by') }}:</span> {{ $payment->verifiedByUser?->name ?? 'System / Finance' }}</div>
            <div class="info-row"><span class="info-label">{{ __('finance.notes') }}:</span> {{ $payment->notes ?? '—' }}</div>
        </div>
    </div>

    <div class="footer">
        <div class="signature-box">
            <div style="font-size: 10px; color: #777;">{{ __('finance.authorized_officer') }}</div>
            <div class="signature-line"></div>
            <div style="font-size: 11px; font-weight: bold; margin-top: 4px;">{{ $payment->verifiedByUser?->name ?? 'Finance Department' }}</div>
            <div style="font-size: 10px; color: #777;">PT Indo International Gate</div>
        </div>
    </div>
</body>
</html>
