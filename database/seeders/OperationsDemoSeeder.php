<?php

namespace Database\Seeders;

use App\Domain\Booking\Models\BookingGuest;
use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Catalog\Models\Partner;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\VendorPayment;
use App\Domain\Lead\Models\Lead;
use App\Domain\Lead\Models\LeadActivity;
use App\Domain\Lead\Models\Quotation;
use App\Domain\Lead\Models\QuotationItem;
use App\Domain\Packaging\Models\Package;
use App\Enums\BookingStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\QuotationStatus;
use App\Models\Branch;
use App\Models\PricingRule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OperationsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $bali = Branch::find(1) ?? Branch::where('name', 'like', '%Bali%')->first();
        if (!$bali) {
            return;
        }

        $csUser = User::where('email', 'cs.bali@indogate.test')->first() ?? User::first();
        $financeUser = User::where('email', 'finance.bali@indogate.test')->first() ?? User::first();
        $adminUser = User::where('email', 'admin@indogate.test')->first() ?? User::first();

        $packages = Package::where('branch_id', $bali->id)->get();
        if ($packages->isEmpty()) {
            $packages = Package::all();
        }
        $pkg1 = $packages->first();
        $pkg2 = $packages->skip(1)->first() ?? $pkg1;
        $pkg3 = $packages->skip(2)->first() ?? $pkg1;

        $hotelPartner = Partner::where('branch_id', $bali->id)->where('name', 'like', '%Hotel%')->first()
            ?? Partner::where('name', 'like', '%Hotel%')->first();
        $fleetPartner = Partner::where('branch_id', $bali->id)->where('name', 'like', '%Fleet%')->first()
            ?? Partner::where('name', 'like', '%Fleet%')->first();
        $villaPartner = Partner::where('branch_id', $bali->id)->where('name', 'like', '%Villa%')->first()
            ?? Partner::where('name', 'like', '%Villa%')->first();

        // 1. Seed Comprehensive Leads
        $leadsData = [
            // Status: NEW
            [
                'name' => 'Sheikh Faisal Al-Sabah',
                'phone' => '+96598761234',
                'country' => 'KW',
                'locale' => 'ar',
                'source' => LeadSource::WEBSITE,
                'status' => LeadStatus::NEW,
                'assigned_to' => $csUser?->id,
                'follow_up_at' => now()->addDay()->setHour(10)->setMinute(0),
                'note' => 'Permintaan paket VIP Luxury Villa Seminyak 4D3N untuk 4 pax. Butuh supir berbahasa Arab.',
            ],
            [
                'name' => 'Siti Nurhaliza & Family',
                'phone' => '+60123456789',
                'country' => 'MY',
                'locale' => 'en',
                'source' => LeadSource::WEBSITE,
                'status' => LeadStatus::NEW,
                'assigned_to' => $csUser?->id,
                'follow_up_at' => now()->addDays(2)->setHour(14)->setMinute(0),
                'note' => 'Permintaan itinerary ramah anak dan restoran halal tersertifikasi di Bali.',
            ],
            [
                'name' => 'Rian D\'Masiv (Family Holiday)',
                'phone' => '081288776655',
                'country' => 'ID',
                'locale' => 'id',
                'source' => LeadSource::MANUAL,
                'status' => LeadStatus::NEW,
                'assigned_to' => $adminUser?->id,
                'follow_up_at' => now()->addDay()->setHour(11)->setMinute(0),
                'note' => 'Rencana liburan keluarga 6 orang di Nusa Dua dan private sunset dinner di Jimbaran.',
            ],

            // Status: CONTACTED
            [
                'name' => 'Fahad Al-Otaibi',
                'phone' => '+966501122334',
                'country' => 'SA',
                'locale' => 'ar',
                'source' => LeadSource::WEBSITE,
                'status' => LeadStatus::CONTACTED,
                'assigned_to' => $csUser?->id,
                'follow_up_at' => now()->setHour(16)->setMinute(0), // due today
                'note' => 'Sudah dikontak via WhatsApp. Sedang memilih antara villa Ubud atau beachfront Seminyak.',
            ],
            [
                'name' => 'Budi Hartono & Rekan Kantor',
                'phone' => '081122334455',
                'country' => 'ID',
                'locale' => 'id',
                'source' => LeadSource::MANUAL,
                'status' => LeadStatus::CONTACTED,
                'assigned_to' => $csUser?->id,
                'follow_up_at' => now()->subDay()->setHour(9)->setMinute(0), // overdue
                'note' => 'Kebutuhan rombongan corporate outing 16 pax. Memerlukan 2 unit Toyota Hiace Premio.',
            ],

            // Status: QUALIFIED
            [
                'name' => 'Sheikh Tariq Al-Maktoum',
                'phone' => '+971509988776',
                'country' => 'AE',
                'locale' => 'ar',
                'source' => LeadSource::WEBSITE,
                'status' => LeadStatus::QUALIFIED,
                'assigned_to' => $csUser?->id,
                'follow_up_at' => now()->setHour(15)->setMinute(30),
                'note' => 'Kualifikasi selesai: Budget USD 5.000, butuh 2 private villa + Toyota Alphard full day service.',
            ],
            [
                'name' => 'Dr. Irwan Setiawan & Istri',
                'phone' => '081399887766',
                'country' => 'ID',
                'locale' => 'id',
                'source' => LeadSource::MANUAL,
                'status' => LeadStatus::QUALIFIED,
                'assigned_to' => $csUser?->id,
                'follow_up_at' => now()->addDays(2),
                'note' => 'Paket honeymoon Ubud 3D2N dengan private infinity pool dan floating breakfast.',
            ],

            // Status: QUOTED
            [
                'name' => 'Dr. Khalid Bin Walid',
                'phone' => '+97433123456',
                'country' => 'QA',
                'locale' => 'ar',
                'source' => LeadSource::WEBSITE,
                'status' => LeadStatus::QUOTED,
                'assigned_to' => $csUser?->id,
                'follow_up_at' => now()->addDay(),
                'note' => 'Quotation resmi QT-2026-QA01 senilai Rp 38.000.000 telah dikirimkan via WA.',
                'create_quotation' => [
                    'package' => $pkg1,
                    'total' => 38_000_000,
                    'items' => [
                        ['desc' => '3 Malam Villa Mewah Seminyak (2 Kamar)', 'qty' => 1, 'unit' => 24_000_000],
                        ['desc' => 'Sewa Toyota Alphard + Supir Arab 4 Hari', 'qty' => 1, 'unit' => 10_000_000],
                        ['desc' => 'Private Snorkeling & Watersport Package', 'qty' => 4, 'unit' => 1_000_000],
                    ],
                ],
            ],
            [
                'name' => 'Dewi Sartika & Family',
                'phone' => '081234567800',
                'country' => 'ID',
                'locale' => 'id',
                'source' => LeadSource::MANUAL,
                'status' => LeadStatus::QUOTED,
                'assigned_to' => $csUser?->id,
                'follow_up_at' => now()->setHour(14)->setMinute(0),
                'note' => 'Quotation QT-2026-DS02 senilai Rp 22.500.000 sudah dikirimkan, menunggu pembayaran DP.',
                'create_quotation' => [
                    'package' => $pkg2,
                    'total' => 22_500_000,
                    'items' => [
                        ['desc' => 'Hotel Grand Bali Deluxe Room 3 Malam', 'qty' => 2, 'unit' => 7_500_000],
                        ['desc' => 'Toyota Hiace + Driver Pariwisata 3 Hari', 'qty' => 1, 'unit' => 5_500_000],
                        ['desc' => 'Tiket Water Park Day Pass & Tour Guide', 'qty' => 4, 'unit' => 500_000],
                    ],
                ],
            ],

            // Status: WON (Will generate Bookings & Financial Records)
            [
                'name' => 'Mansoor Al-Zahrani',
                'phone' => '+966551239876',
                'country' => 'SA',
                'locale' => 'ar',
                'source' => LeadSource::WEBSITE,
                'status' => LeadStatus::WON,
                'assigned_to' => $csUser?->id,
                'follow_up_at' => null,
                'note' => 'Deal closed! Booking dikonfirmasi untuk liburan keluarga 4 pax.',
                'booking' => [
                    'code' => 'BK-2026-BL01',
                    'status' => BookingStatus::IN_PROGRESS,
                    'departure' => now()->subDay(),
                    'return' => now()->addDays(3),
                    'total' => 28_500_000,
                    'package' => $pkg1,
                    'items' => [
                        ['desc' => 'Paket Bali Tropical Paradise & Culture 4D3N (Family)', 'qty' => 1, 'unit' => 28_500_000],
                    ],
                    'customer_payments' => [
                        [
                            'type' => Payment::TYPE_FULL_PAYMENT,
                            'amount' => 28_500_000,
                            'fee' => 570_000, // 2% MDR international card
                            'channel' => 'international_card',
                        ],
                    ],
                    'vendor_payments' => [
                        [
                            'partner' => $hotelPartner,
                            'amount' => 11_400_000,
                            'desc' => 'Akomodasi 3 Malam Deluxe Suite Mansoor Al-Zahrani',
                        ],
                        [
                            'partner' => $fleetPartner,
                            'amount' => 6_800_000,
                            'desc' => 'Sewa Toyota Alphard 4 Hari + BBM + Supir',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Bambang Soediro Private Tour',
                'phone' => '081198765432',
                'country' => 'ID',
                'locale' => 'id',
                'source' => LeadSource::MANUAL,
                'status' => LeadStatus::WON,
                'assigned_to' => $csUser?->id,
                'follow_up_at' => null,
                'note' => 'Perjalanan selesai dengan ulasan bintang 5. Semua pembayaran vendor dan tamu tuntas.',
                'booking' => [
                    'code' => 'BK-2026-BL02',
                    'status' => BookingStatus::COMPLETED,
                    'departure' => now()->subDays(10),
                    'return' => now()->subDays(6),
                    'total' => 42_000_000,
                    'package' => $pkg2,
                    'items' => [
                        ['desc' => 'East Bali Scenic Heritage & Snorkeling VIP 5D4N', 'qty' => 1, 'unit' => 42_000_000],
                    ],
                    'customer_payments' => [
                        [
                            'type' => Payment::TYPE_DOWN_PAYMENT,
                            'amount' => 21_000_000,
                            'fee' => 4_500, // Bank Transfer Virtual Account
                            'channel' => 'bank_transfer',
                        ],
                        [
                            'type' => Payment::TYPE_FULL_PAYMENT,
                            'amount' => 21_000_000,
                            'fee' => 4_500,
                            'channel' => 'bank_transfer',
                        ],
                    ],
                    'vendor_payments' => [
                        [
                            'partner' => $villaPartner,
                            'amount' => 18_000_000,
                            'desc' => 'Pelunasan Private Villa Ubud 4 Malam',
                        ],
                        [
                            'partner' => $fleetPartner,
                            'amount' => 8_500_000,
                            'desc' => 'Sewa 2 Unit Hiace + Tour Guide Snorkeling',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Omar Al-Ghamdi & Spouse',
                'phone' => '+966509876543',
                'country' => 'SA',
                'locale' => 'ar',
                'source' => LeadSource::WEBSITE,
                'status' => LeadStatus::WON,
                'assigned_to' => $csUser?->id,
                'follow_up_at' => null,
                'note' => 'Tamu lunas via kartu kredit internasional. Berangkat minggu depan.',
                'booking' => [
                    'code' => 'BK-2026-BL03',
                    'status' => BookingStatus::PAID,
                    'departure' => now()->addDays(5),
                    'return' => now()->addDays(9),
                    'total' => 35_000_000,
                    'package' => $pkg3,
                    'items' => [
                        ['desc' => 'Seminyak Luxury Beach & Sunset Break 4D3N Honeymoon', 'qty' => 1, 'unit' => 35_000_000],
                    ],
                    'customer_payments' => [
                        [
                            'type' => Payment::TYPE_FULL_PAYMENT,
                            'amount' => 35_000_000,
                            'fee' => 875_000, // 2.5% MDR
                            'channel' => 'international_card',
                        ],
                    ],
                    'vendor_payments' => [
                        [
                            'partner' => $hotelPartner,
                            'amount' => 15_500_000,
                            'desc' => 'Deposit & Pelunasan Hotel Seminyak Honeymoon Suite',
                        ],
                        [
                            'partner' => $fleetPartner,
                            'amount' => 6_500_000,
                            'desc' => 'Penjemputan VIP Airport & Sewa Alphard 4 Hari',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Anindya Bakrie Group Outing',
                'phone' => '081809876543',
                'country' => 'ID',
                'locale' => 'id',
                'source' => LeadSource::MANUAL,
                'status' => LeadStatus::WON,
                'assigned_to' => $adminUser?->id,
                'follow_up_at' => now()->addDays(3),
                'note' => 'DP 50% sudah diterima dan diverifikasi finance. Pelunasan H-3 keberangkatan.',
                'booking' => [
                    'code' => 'BK-2026-BL04',
                    'status' => BookingStatus::PARTIALLY_PAID,
                    'departure' => now()->addDays(12),
                    'return' => now()->addDays(16),
                    'total' => 62_000_000,
                    'package' => $pkg1,
                    'items' => [
                        ['desc' => 'Executive Bali Retreat & Nusa Penida Explorer 5D4N', 'qty' => 1, 'unit' => 62_000_000],
                    ],
                    'customer_payments' => [
                        [
                            'type' => Payment::TYPE_DOWN_PAYMENT,
                            'amount' => 31_000_000,
                            'fee' => 4_500,
                            'channel' => 'bank_transfer',
                        ],
                    ],
                    'vendor_payments' => [
                        [
                            'partner' => $villaPartner,
                            'amount' => 20_000_000,
                            'desc' => 'Uang Muka Villa Resort Nusa Dua 12 Kamar',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Hussain Al-Najjar',
                'phone' => '+971521239988',
                'country' => 'AE',
                'locale' => 'ar',
                'source' => LeadSource::WEBSITE,
                'status' => LeadStatus::WON,
                'assigned_to' => $csUser?->id,
                'follow_up_at' => null,
                'note' => 'Booking dikonfirmasi, menunggu jadwal keberangkatan bulan depan.',
                'booking' => [
                    'code' => 'BK-2026-BL05',
                    'status' => BookingStatus::CONFIRMED,
                    'departure' => now()->addDays(20),
                    'return' => now()->addDays(24),
                    'total' => 24_000_000,
                    'package' => $pkg2,
                    'items' => [
                        ['desc' => 'Ultimate Bali Family Leisure & Waterpark 4D3N', 'qty' => 1, 'unit' => 24_000_000],
                    ],
                    'customer_payments' => [
                        [
                            'type' => Payment::TYPE_DOWN_PAYMENT,
                            'amount' => 12_000_000,
                            'fee' => 240_000, // 2% MDR
                            'channel' => 'international_card',
                        ],
                    ],
                    'vendor_payments' => [
                        [
                            'partner' => $hotelPartner,
                            'amount' => 7_500_000,
                            'desc' => 'Deposit Kamar Family Suite Hotel Grand Bali',
                        ],
                    ],
                ],
            ],

            // Status: LOST
            [
                'name' => 'Rudy Hartono',
                'phone' => '081345678901',
                'country' => 'ID',
                'locale' => 'id',
                'source' => LeadSource::MANUAL,
                'status' => LeadStatus::LOST,
                'assigned_to' => $csUser?->id,
                'follow_up_at' => null,
                'lost_reason' => 'Klien memilih destinasi Labuan Bajo',
                'note' => 'Klien mengalihkan anggaran tahun ini untuk sewa kapal phinisi di Labuan Bajo.',
            ],
            [
                'name' => 'Abdulrahman Al-Mutawa',
                'phone' => '+96599112233',
                'country' => 'KW',
                'locale' => 'ar',
                'source' => LeadSource::WEBSITE,
                'status' => LeadStatus::LOST,
                'assigned_to' => $csUser?->id,
                'follow_up_at' => null,
                'lost_reason' => 'Jadwal cuti kerja dibatalkan kantor',
                'note' => 'Klien menunda rencana liburan ke kuartal pertama tahun depan.',
            ],
        ];

        foreach ($leadsData as $data) {
            $bookingData = $data['booking'] ?? null;
            $quotationData = $data['create_quotation'] ?? null;
            $note = $data['note'] ?? '';

            // Check if lead already exists by phone to allow safe re-running
            $lead = Lead::where('phone', $data['phone'])->first();
            if (!$lead) {
                $lead = Lead::create([
                    'branch_id' => $bali->id,
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'country' => $data['country'],
                    'locale' => $data['locale'],
                    'source' => $data['source'],
                    'status' => $data['status'],
                    'assigned_to' => $data['assigned_to'],
                    'follow_up_at' => $data['follow_up_at'],
                    'lost_reason' => $data['lost_reason'] ?? null,
                ]);

                if ($note) {
                    LeadActivity::create([
                        'lead_id' => $lead->id,
                        'user_id' => $data['assigned_to'] ?? $csUser?->id,
                        'type' => 'note',
                        'note' => $note,
                        'created_at' => now()->subHours(rand(1, 48)),
                    ]);
                }
            }

            // Create Quotation if needed
            $quotation = null;
            if ($quotationData) {
                $quotation = Quotation::firstOrCreate(
                    ['lead_id' => $lead->id],
                    [
                        'branch_id' => $bali->id,
                        'package_id' => $quotationData['package']?->id,
                        'token' => Str::random(48),
                        'currency' => 'IDR',
                        'locked_rate' => 1.0,
                        'valid_until' => now()->addDays(7),
                        'status' => QuotationStatus::SENT,
                    ]
                );

                if ($quotation->items()->count() === 0) {
                    foreach ($quotationData['items'] as $item) {
                        QuotationItem::create([
                            'quotation_id' => $quotation->id,
                            'description' => ['id' => $item['desc'], 'en' => $item['desc']],
                            'qty' => $item['qty'],
                            'unit_price_minor' => $item['unit'],
                            'total_minor' => $item['qty'] * $item['unit'],
                        ]);
                    }
                }
            }

            // Create Booking, Payments, and Vendor Payments if won
            if ($bookingData) {
                $booking = PackageBooking::where('code', $bookingData['code'])->first();
                if (!$booking) {
                    // Create Quotation for the booking
                    $quotation = Quotation::create([
                        'branch_id' => $bali->id,
                        'lead_id' => $lead->id,
                        'package_id' => $bookingData['package']?->id,
                        'token' => Str::random(48),
                        'currency' => 'IDR',
                        'locked_rate' => 1.0,
                        'valid_until' => now()->addDays(14),
                        'status' => QuotationStatus::SENT,
                    ]);

                    foreach ($bookingData['items'] as $item) {
                        QuotationItem::create([
                            'quotation_id' => $quotation->id,
                            'description' => ['id' => $item['desc'], 'en' => $item['desc']],
                            'qty' => $item['qty'],
                            'unit_price_minor' => $item['unit'],
                            'total_minor' => $item['qty'] * $item['unit'],
                        ]);
                    }

                    $booking = PackageBooking::create([
                        'branch_id' => $bali->id,
                        'created_by' => $csUser?->id,
                        'quotation_id' => $quotation->id,
                        'code' => $bookingData['code'],
                        'status' => $bookingData['status'],
                        'departure_date' => $bookingData['departure'],
                        'return_date' => $bookingData['return'],
                        'total_minor' => $bookingData['total'],
                        'currency' => 'IDR',
                        'driver_gender_preference' => null,
                    ]);

                    // Create lead guest
                    BookingGuest::create([
                        'booking_id' => $booking->id,
                        'name' => $data['name'],
                        'passport_number' => 'P' . rand(10000000, 99999999),
                        'nationality' => $data['country'],
                        'is_lead_guest' => true,
                    ]);

                    // Create customer payments
                    foreach ($bookingData['customer_payments'] as $pay) {
                        Payment::create([
                            'branch_id' => $bali->id,
                            'booking_id' => $booking->id,
                            'type' => $pay['type'],
                            'amount_minor' => $pay['amount'],
                            'currency' => 'IDR',
                            'fx_rate' => 1.0,
                            'idr_equivalent_minor' => $pay['amount'],
                            'channel_fee_minor' => $pay['fee'],
                            'channel' => $pay['channel'],
                            'notes' => 'Pembayaran ' . ($pay['type'] === Payment::TYPE_DOWN_PAYMENT ? 'Uang Muka (DP)' : 'Pelunasan') . ' via ' . $pay['channel'],
                            'status' => Payment::STATUS_VERIFIED,
                            'created_by' => $csUser?->id,
                            'verified_by' => $financeUser?->id,
                            'verified_at' => now()->subDays(1),
                        ]);
                    }

                    // Create vendor payments
                    foreach ($bookingData['vendor_payments'] as $vp) {
                        VendorPayment::create([
                            'branch_id' => $bali->id,
                            'booking_id' => $booking->id,
                            'partner_id' => $vp['partner']?->id ?? 1,
                            'amount_minor' => $vp['amount'],
                            'currency' => 'IDR',
                            'fx_rate' => 1.0,
                            'idr_equivalent_minor' => $vp['amount'],
                            'description' => $vp['desc'],
                            'paid_at' => now()->subDays(rand(1, 5)),
                            'created_by' => $financeUser?->id,
                        ]);
                    }
                }
            }
        }

        // 2. Seed Dynamic Pricing Rules (Aturan Harga Dinamis)
        $pricingRulesData = [
            [
                'service_type' => 'hotel',
                'season_start' => now()->subDays(10)->toDateString(),
                'season_end' => now()->addDays(25)->toDateString(),
                'markup_percent' => 20.00,
                'tier' => 'Musim Wisatawan Mancanegara (Autumn Inbound)',
            ],
            [
                'service_type' => 'driver',
                'season_start' => now()->subDays(5)->toDateString(),
                'season_end' => now()->addDays(40)->toDateString(),
                'markup_percent' => 18.50,
                'tier' => 'Operasional Layanan Harian Wisata',
            ],
            [
                'service_type' => 'all',
                'season_start' => '2026-12-15',
                'season_end' => '2027-01-08',
                'markup_percent' => 35.00,
                'tier' => 'Peak Season Akhir Tahun (Nataru)',
            ],
            [
                'service_type' => 'hotel',
                'season_start' => '2026-07-01',
                'season_end' => '2026-08-31',
                'markup_percent' => 30.00,
                'tier' => 'Musim Liburan Musim Panas GCC (Summer Holiday)',
            ],
            [
                'service_type' => 'driver',
                'season_start' => '2026-03-25',
                'season_end' => '2026-04-10',
                'markup_percent' => 25.00,
                'tier' => 'Peak Libur Hari Raya Idul Fitri & Mudik',
            ],
            [
                'service_type' => 'flight',
                'season_start' => '2026-10-01',
                'season_end' => '2026-11-30',
                'markup_percent' => 15.00,
                'tier' => 'Musim Gugur / Pre-Holiday Promo Tiket',
            ],
            [
                'service_type' => 'all',
                'season_start' => '2027-02-01',
                'season_end' => '2027-04-30',
                'markup_percent' => 10.00,
                'tier' => 'Low Season Q1 Reguler',
            ],
        ];

        foreach (Branch::all() as $branch) {
            foreach ($pricingRulesData as $ruleData) {
                PricingRule::withoutGlobalScopes()->firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'service_type' => $ruleData['service_type'],
                        'tier' => $ruleData['tier'],
                    ],
                    [
                        'season_start' => $ruleData['season_start'],
                        'season_end' => $ruleData['season_end'],
                        'markup_percent' => $ruleData['markup_percent'],
                    ]
                );
            }
        }
    }
}
