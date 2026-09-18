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

/**
 * PRD M4 step: sample data so the next module (M5 Package Builder) and the
 * Pricing Simulator have something to compute against out of the box.
 */
class PricingSeeder extends Seeder
{
    public function run(): void
    {
        Currency::firstOrCreate(['code' => 'IDR'], ['symbol' => 'Rp', 'decimal_places' => 0, 'is_active' => true]);
        Currency::firstOrCreate(['code' => 'USD'], ['symbol' => '$', 'decimal_places' => 2, 'is_active' => true]);
        Currency::firstOrCreate(['code' => 'SAR'], ['symbol' => 'SR', 'decimal_places' => 2, 'is_active' => true]);

        $admin = User::role('Super Admin')->first();

        if (! ExchangeRate::where('currency', 'USD')->exists()) {
            ExchangeRate::create(['currency' => 'USD', 'rate' => '15800.00000000', 'effective_from' => now()->subDay(), 'created_by' => $admin->id]);
        }
        if (! ExchangeRate::where('currency', 'SAR')->exists()) {
            ExchangeRate::create(['currency' => 'SAR', 'rate' => '4213.00000000', 'effective_from' => now()->subDay(), 'created_by' => $admin->id]);
        }

        PaymentChannelCost::firstOrCreate(
            ['channel' => PaymentChannel::BANK_TRANSFER->value],
            ['percent_fee' => 0, 'flat_fee_minor' => 0, 'currency' => 'IDR'],
        );
        PaymentChannelCost::firstOrCreate(
            ['channel' => PaymentChannel::INTERNATIONAL_CARD->value],
            ['percent_fee' => 550, 'flat_fee_minor' => 5000, 'currency' => 'IDR'],
        );

        foreach (Branch::all() as $branch) {
            $seasons = [
                ['name' => 'Peak Season', 'date_from' => '2026-12-15', 'date_to' => '2027-01-05', 'type' => SeasonType::PEAK],
                ['name' => 'High Season', 'date_from' => '2026-07-01', 'date_to' => '2026-08-31', 'type' => SeasonType::HIGH],
                ['name' => 'Low Season', 'date_from' => '2026-02-01', 'date_to' => '2026-05-31', 'type' => SeasonType::LOW],
            ];

            foreach ($seasons as $season) {
                Season::withoutGlobalScopes()->firstOrCreate(
                    ['branch_id' => $branch->id, 'name' => $season['name']],
                    ['date_from' => $season['date_from'], 'date_to' => $season['date_to'], 'type' => $season['type']],
                );
            }

            $margins = [
                ['product_type' => InventoryItemType::ROOM, 'season_type' => SeasonType::PEAK, 'margin_percent' => 3500],
                ['product_type' => InventoryItemType::ROOM, 'season_type' => SeasonType::HIGH, 'margin_percent' => 2500],
                ['product_type' => InventoryItemType::ROOM, 'season_type' => SeasonType::LOW, 'margin_percent' => 1500],
                ['product_type' => InventoryItemType::VEHICLE, 'season_type' => null, 'margin_percent' => 2000],
                ['product_type' => InventoryItemType::TICKET, 'season_type' => null, 'margin_percent' => 1000],
                ['product_type' => InventoryItemType::ACTIVITY, 'season_type' => null, 'margin_percent' => 2000],
            ];

            foreach ($margins as $margin) {
                MarginRule::withoutGlobalScopes()->firstOrCreate(
                    ['branch_id' => $branch->id, 'product_type' => $margin['product_type'], 'season_type' => $margin['season_type']],
                    ['margin_percent' => $margin['margin_percent'], 'is_active' => true],
                );
            }
        }
    }
}
