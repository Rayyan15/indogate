<?php

namespace Database\Seeders;

use App\Domain\Pricing\Models\Currency;
use App\Domain\Pricing\Models\ExchangeRate;
use App\Domain\Pricing\Models\MarginRule;
use App\Domain\Pricing\Models\PaymentChannelCost;
use App\Domain\Pricing\Models\Season;
use App\Enums\InventoryItemType;
use App\Enums\PaymentChannel;
use App\Enums\SeasonType;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Models\Activity;

/**
 * Demo data for M4 — NOT part of the default DatabaseSeeder chain. Run it
 * on demand (`php artisan db:seed --class=PricingDemoSeeder`) after the
 * normal seeders when you want every M4 screen to show a realistic,
 * lived-in case instead of the bare minimum PricingSeeder leaves behind.
 *
 * Each block below is labeled with which PRD M4 flow it makes visible.
 */
class PricingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::role('Super Admin')->firstOrFail();
        $bali = Branch::where('code', 'BALI')->firstOrFail();
        $jkt = Branch::where('code', 'JKT')->firstOrFail();

        $this->currencies();
        $this->exchangeRateHistory($admin);
        $this->seasons($bali, $jkt);
        $this->marginRules($bali, $jkt);
        $this->channelCosts();
        $this->overrideHistory($admin);

        $this->command?->info('PricingDemoSeeder done — check Mesin Harga & Kurs screens for each flow.');
    }

    /**
     * Flow: Currencies. One inactive currency (KRW) to show that toggle
     * actually hides it from the simulator's usable set, not just cosmetic.
     */
    private function currencies(): void
    {
        Currency::updateOrCreate(['code' => 'IDR'], ['symbol' => 'Rp', 'decimal_places' => 0, 'is_active' => true]);
        Currency::updateOrCreate(['code' => 'USD'], ['symbol' => '$', 'decimal_places' => 2, 'is_active' => true]);
        Currency::updateOrCreate(['code' => 'SAR'], ['symbol' => 'SR', 'decimal_places' => 2, 'is_active' => true]);
        Currency::updateOrCreate(['code' => 'EUR'], ['symbol' => '€', 'decimal_places' => 2, 'is_active' => true]);
        Currency::updateOrCreate(['code' => 'KRW'], ['symbol' => '₩', 'decimal_places' => 0, 'is_active' => false]);
    }

    /**
     * Flow: Exchange Rates. Three insert-only rows per currency spread over
     * the last two months so the "riwayat kurs, tidak pernah ditimpa" list
     * actually has a history to scroll through, not just one row.
     *
     * Fixed calendar dates (not now()->subDays()) so re-running this seeder
     * is idempotent — a relative date would produce a new row every run.
     */
    private function exchangeRateHistory(User $admin): void
    {
        $history = [
            'USD' => [
                ['2026-07-20 09:00:00', '15650.00000000'],
                ['2026-08-24 09:00:00', '15800.00000000'],
                ['2026-09-15 09:00:00', '15920.00000000'],
            ],
            'SAR' => [
                ['2026-07-20 09:00:00', '4150.00000000'],
                ['2026-08-29 09:00:00', '4213.00000000'],
            ],
            'EUR' => [
                ['2026-08-04 09:00:00', '16900.00000000'],
                ['2026-09-16 09:00:00', '17200.00000000'],
            ],
        ];

        foreach ($history as $currency => $rows) {
            foreach ($rows as [$effectiveFrom, $rate]) {
                ExchangeRate::firstOrCreate(
                    ['currency' => $currency, 'effective_from' => $effectiveFrom],
                    ['rate' => $rate, 'created_by' => $admin->id],
                );
            }
        }
    }

    /**
     * Flow: Season Calendar. Bali runs a beach-destination calendar (long
     * peak around year-end/mid-year holidays); Jakarta — a business/MICE
     * destination — barely has a "peak" at all. Different shape per branch
     * on purpose, so RuleResolver's per-branch lookup is visibly doing
     * something rather than every branch looking identical.
     */
    private function seasons(Branch $bali, Branch $jkt): void
    {
        $this->season($bali, 'Peak Tahun Baru', '2026-12-20', '2027-01-05', SeasonType::PEAK);
        $this->season($bali, 'Peak Lebaran', '2026-04-15', '2026-04-25', SeasonType::PEAK);
        $this->season($bali, 'High Musim Kemarau', '2026-06-01', '2026-08-31', SeasonType::HIGH);
        $this->season($bali, 'Low Musim Hujan', '2026-01-06', '2026-03-31', SeasonType::LOW);
        $this->season($bali, 'Low Sela', '2026-09-01', '2026-11-30', SeasonType::LOW);

        $this->season($jkt, 'High Akhir Tahun Korporat', '2026-11-01', '2026-12-19', SeasonType::HIGH);
        $this->season($jkt, 'Low Reguler', '2026-01-06', '2026-10-31', SeasonType::LOW);
    }

    private function season(Branch $branch, string $name, string $from, string $to, SeasonType $type): void
    {
        Season::withoutGlobalScopes()->firstOrCreate(
            ['branch_id' => $branch->id, 'name' => $name],
            ['date_from' => $from, 'date_to' => $to, 'type' => $type],
        );
    }

    /**
     * Flow: Margin Rules. Bali (leisure, high demand) carries thicker
     * margins than Jakarta (corporate, price-sensitive) for the same
     * product type — the kind of branch-level pricing policy difference
     * PRD M4 exists to let Finance configure without touching code. One
     * rule is left inactive to show a retired rule sitting in the list.
     */
    private function marginRules(Branch $bali, Branch $jkt): void
    {
        $this->marginRule($bali, InventoryItemType::ROOM, SeasonType::PEAK, 4000);
        $this->marginRule($bali, InventoryItemType::ROOM, SeasonType::HIGH, 2800);
        $this->marginRule($bali, InventoryItemType::ROOM, SeasonType::LOW, 1500);
        $this->marginRule($bali, InventoryItemType::VEHICLE, null, 2000);
        $this->marginRule($bali, InventoryItemType::ACTIVITY, null, 2500);
        $this->marginRule($bali, InventoryItemType::TICKET, null, 800);
        $this->marginRule($bali, InventoryItemType::ROOM, null, 1000, isActive: false);

        $this->marginRule($jkt, InventoryItemType::ROOM, SeasonType::HIGH, 2000);
        $this->marginRule($jkt, InventoryItemType::ROOM, SeasonType::LOW, 1200);
        $this->marginRule($jkt, InventoryItemType::VEHICLE, null, 1500);
        $this->marginRule($jkt, InventoryItemType::TICKET, null, 700);
    }

    private function marginRule(Branch $branch, InventoryItemType $type, ?SeasonType $season, int $marginBasisPoints, bool $isActive = true): void
    {
        MarginRule::withoutGlobalScopes()->updateOrCreate(
            ['branch_id' => $branch->id, 'product_type' => $type, 'season_type' => $season],
            ['margin_percent' => $marginBasisPoints, 'is_active' => $isActive],
        );
    }

    /**
     * Flow: Payment Channel Costs. Bank transfer stays free (local guests
     * pay this way by default); a card surcharge that mirrors what an
     * international gateway actually charges Indogate.
     */
    private function channelCosts(): void
    {
        PaymentChannelCost::updateOrCreate(
            ['channel' => PaymentChannel::BANK_TRANSFER->value],
            ['percent_fee' => 0, 'flat_fee_minor' => 0, 'currency' => 'IDR'],
        );
        PaymentChannelCost::updateOrCreate(
            ['channel' => PaymentChannel::INTERNATIONAL_CARD->value],
            ['percent_fee' => 590, 'flat_fee_minor' => 7500, 'currency' => 'IDR'],
        );
    }

    /**
     * Flow: Manual override + activity log. There's no quotation table
     * yet (M6), so a "past override" is simulated the same way the real
     * Pricing Simulator logs one — an activity('pricing') entry with
     * amounts + a mandatory reason — so the activity log screen already
     * has believable history to show instead of being empty on first
     * look.
     */
    private function overrideHistory(User $admin): void
    {
        $cases = [
            [
                'when' => '2026-09-04 14:30:00',
                'sell_idr_minor' => 18_500_000,
                'override_amount_minor' => 16_000_000,
                'override_currency' => 'IDR',
                'reason' => 'Diskon loyalitas agen tetap PT Nusantara Wisata — booking ke-12 tahun ini.',
            ],
            [
                'when' => '2026-09-13 10:15:00',
                'sell_idr_minor' => 6_200_000,
                'override_amount_minor' => 5_800_000,
                'override_currency' => 'IDR',
                'reason' => 'Approval Finance: kompensasi keterlambatan konfirmasi hotel 1 hari.',
            ],
        ];

        foreach ($cases as $case) {
            $exists = Activity::query()
                ->where('log_name', 'pricing')
                ->where('properties->reason', $case['reason'])
                ->exists();

            if ($exists) {
                continue;
            }

            activity('pricing')
                ->causedBy($admin)
                ->withProperties([
                    'sell_idr_minor' => $case['sell_idr_minor'],
                    'override_amount_minor' => $case['override_amount_minor'],
                    'override_currency' => $case['override_currency'],
                    'reason' => $case['reason'],
                ])
                ->log('Manual price override applied via Pricing Simulator');

            Activity::latest()->first()?->forceFill(['created_at' => $case['when'], 'updated_at' => $case['when']])->save();
        }
    }
}
