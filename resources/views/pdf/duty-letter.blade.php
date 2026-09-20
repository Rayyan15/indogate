<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}" dir="{{ ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('fleet.duty_letter') }} — {{ $assignment->booking->code }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1A1A19; line-height: 1.5; margin: 24px; }
        .header { border-bottom: 2px solid #C41230; padding-bottom: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start; }
        .brand { font-size: 22px; font-weight: bold; letter-spacing: 0.08em; color: #1A1A19; }
        .brand span { color: #C41230; }
        .title { font-size: 18px; font-weight: bold; margin-top: 4px; text-transform: uppercase; }
        .subtitle { font-size: 11px; color: #575753; margin-top: 2px; }
        .section-title { font-size: 13px; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #E2E2E0; padding-bottom: 4px; margin-top: 18px; margin-bottom: 8px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { text-align: {{ ($locale ?? app()->getLocale()) === 'ar' ? 'right' : 'left' }}; padding: 6px 10px; border-bottom: 1px solid #E2E2E0; font-size: 11px; }
        th { background: #F8F8F7; text-transform: uppercase; letter-spacing: 0.05em; font-size: 10px; color: #575753; }
        .grid { display: table; width: 100%; margin-top: 10px; }
        .col { display: table-cell; width: 50%; vertical-align: top; padding-right: 15px; }
        .info-row { margin-bottom: 6px; font-size: 11px; }
        .info-label { font-weight: 600; color: #575753; }
        .footer { margin-top: 40px; display: table; width: 100%; }
        .signature-box { display: table-cell; width: 50%; text-align: center; }
        .signature-line { margin-top: 60px; border-top: 1px solid #1A1A19; width: 180px; margin-left: auto; margin-right: auto; }
        .badge { display: inline-block; padding: 2px 8px; font-size: 10px; font-weight: bold; border-radius: 4px; background: #EBF3FC; color: #185FA5; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">INDO<span>GATE</span></div>
            <div class="subtitle">PT Indo International Gate · {{ $assignment->branch?->name }}</div>
        </div>
        <div style="text-align: {{ ($locale ?? app()->getLocale()) === 'ar' ? 'left' : 'right' }};">
            <div class="title">{{ __('fleet.surat_tugas') }}</div>
            <div class="subtitle">{{ __('fleet.reference_number') }}: ST/{{ $assignment->branch?->code }}/{{ $assignment->id }}/{{ $assignment->booking->code }}</div>
            <div class="subtitle">{{ __('fleet.issued_date') }}: {{ now()->translatedFormat('d F Y') }}</div>
        </div>
    </div>

    <div class="grid">
        <div class="col">
            <div class="section-title">{{ __('fleet.assigned_driver') }}</div>
            <div class="info-row"><span class="info-label">{{ __('fleet.driver_name') }}:</span> <strong>{{ $assignment->driver?->name }}</strong></div>
            <div class="info-row"><span class="info-label">{{ __('fleet.driver_gender') }}:</span> {{ $assignment->driver?->gender === 'female' ? __('fleet.gender_female') : __('fleet.gender_male') }}</div>
            <div class="info-row"><span class="info-label">{{ __('fleet.driver_phone') }}:</span> {{ $assignment->driver?->phone ?? '—' }}</div>
            @if(!empty($assignment->driver?->languages))
                <div class="info-row"><span class="info-label">{{ __('fleet.driver_languages') }}:</span> {{ implode(', ', (array) $assignment->driver->languages) }}</div>
            @endif
        </div>
        <div class="col">
            <div class="section-title">{{ __('fleet.assigned_vehicle') }}</div>
            @if($assignment->vehicle)
                <div class="info-row"><span class="info-label">{{ __('fleet.vehicle_plate') }}:</span> <strong>{{ $assignment->vehicle->plate }}</strong></div>
                <div class="info-row"><span class="info-label">{{ __('fleet.vehicle_type') }}:</span> {{ $assignment->vehicle->type }}</div>
                <div class="info-row"><span class="info-label">{{ __('fleet.vehicle_capacity') }}:</span> {{ __('fleet.capacity_pax', ['count' => $assignment->vehicle->capacity]) }}</div>
            @else
                <div class="info-row" style="color: #888;">— ({{ __('fleet.unassigned') }})</div>
            @endif
        </div>
    </div>

    <div class="section-title">{{ __('fleet.assignment_period') }} & {{ __('fleet.booking_code') }}</div>
    <div class="grid">
        <div class="col">
            <div class="info-row"><span class="info-label">{{ __('fleet.booking_code') }}:</span> <strong>{{ $assignment->booking->code }}</strong></div>
            <div class="info-row"><span class="info-label">{{ __('fleet.departure_date') }}:</span> {{ $assignment->booking->departure_date->translatedFormat('d F Y') }}</div>
            @if($assignment->booking->return_date)
                <div class="info-row"><span class="info-label">{{ __('fleet.return_date') }}:</span> {{ $assignment->booking->return_date->translatedFormat('d F Y') }}</div>
            @endif
        </div>
        <div class="col">
            <div class="info-row"><span class="info-label">{{ __('fleet.assignment_period') }}:</span> <strong>{{ $assignment->date_from->translatedFormat('d M Y') }} s/d {{ $assignment->date_to->translatedFormat('d M Y') }}</strong></div>
            <div class="info-row"><span class="info-label">{{ __('fleet.notes') }}:</span> {{ $assignment->notes ?? '—' }}</div>
        </div>
    </div>

    <div class="section-title">{{ __('fleet.guest_manifest') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('fleet.guest_name') }}</th>
                <th>{{ __('booking.pdf.nationality') }}</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assignment->booking->guests as $guest)
                <tr>
                    <td><strong>{{ $guest->name }}</strong></td>
                    <td>{{ $guest->nationality ?? '—' }}</td>
                    <td>
                        @if($guest->is_lead_guest)
                            <span class="badge">{{ __('booking.pdf.lead_guest') }}</span>
                        @else
                            Tamu
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; color: #888;">Belum ada daftar tamu tercatat.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div class="signature-box" style="float: left;">
            <p style="font-size: 11px;">Driver Bertugas,</p>
            <div class="signature-line"></div>
            <p style="font-size: 11px; font-weight: bold; margin-top: 4px;">{{ $assignment->driver?->name }}</p>
        </div>
        <div class="signature-box" style="float: right;">
            <p style="font-size: 11px;">{{ __('fleet.authorized_by') }},</p>
            <div class="signature-line"></div>
            <p style="font-size: 11px; font-weight: bold; margin-top: 4px;">Operasional {{ $assignment->branch?->name }}</p>
        </div>
        <div style="clear: both;"></div>
        <p style="font-size: 10px; color: #888; text-align: center; margin-top: 25px;">
            {{ __('fleet.duty_signature_note') }} · Generated on {{ now()->toDayDateTimeString() }}
        </p>
    </div>
</body>
</html>
