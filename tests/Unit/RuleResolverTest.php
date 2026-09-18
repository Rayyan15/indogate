<?php

namespace Tests\Unit;

use App\Domain\Pricing\Exceptions\NoApplicableMarginRuleException;
use App\Domain\Pricing\Models\MarginRule;
use App\Domain\Pricing\Models\Season;
use App\Domain\Pricing\RuleResolver;
use App\Enums\InventoryItemType;
use App\Enums\SeasonType;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuleResolverTest extends TestCase
{
    use RefreshDatabase;

    private function branch(): Branch
    {
        return Branch::create(['name' => 'Bali', 'code' => 'BALI', 'timezone' => 'Asia/Makassar', 'is_active' => true]);
    }

    public function test_rule_is_selected_from_departure_date_not_creation_date(): void
    {
        $branch = $this->branch();

        Season::withoutGlobalScopes()->create([
            'branch_id' => $branch->id, 'name' => 'Peak', 'date_from' => '2026-12-01', 'date_to' => '2026-12-31', 'type' => SeasonType::PEAK,
        ]);
        Season::withoutGlobalScopes()->create([
            'branch_id' => $branch->id, 'name' => 'Low', 'date_from' => '2026-02-01', 'date_to' => '2026-02-28', 'type' => SeasonType::LOW,
        ]);

        MarginRule::withoutGlobalScopes()->create([
            'branch_id' => $branch->id, 'product_type' => InventoryItemType::ROOM, 'season_type' => SeasonType::PEAK, 'margin_percent' => 3500, 'is_active' => true,
        ]);
        MarginRule::withoutGlobalScopes()->create([
            'branch_id' => $branch->id, 'product_type' => InventoryItemType::ROOM, 'season_type' => SeasonType::LOW, 'margin_percent' => 2000, 'is_active' => true,
        ]);

        $resolver = new RuleResolver;

        $rule = $resolver->resolve($branch->id, InventoryItemType::ROOM, new \DateTimeImmutable('2026-12-15'));
        $this->assertSame(3500, $rule->margin_percent);

        $rule = $resolver->resolve($branch->id, InventoryItemType::ROOM, new \DateTimeImmutable('2026-02-15'));
        $this->assertSame(2000, $rule->margin_percent);
    }

    public function test_falls_back_to_rule_without_season_when_no_season_specific_rule_exists(): void
    {
        $branch = $this->branch();

        Season::withoutGlobalScopes()->create([
            'branch_id' => $branch->id, 'name' => 'High', 'date_from' => '2026-07-01', 'date_to' => '2026-07-31', 'type' => SeasonType::HIGH,
        ]);

        MarginRule::withoutGlobalScopes()->create([
            'branch_id' => $branch->id, 'product_type' => InventoryItemType::VEHICLE, 'season_type' => null, 'margin_percent' => 1500, 'is_active' => true,
        ]);

        $resolver = new RuleResolver;
        $rule = $resolver->resolve($branch->id, InventoryItemType::VEHICLE, new \DateTimeImmutable('2026-07-15'));

        $this->assertSame(1500, $rule->margin_percent);
    }

    public function test_throws_when_no_rule_matches_at_all(): void
    {
        $branch = $this->branch();

        $this->expectException(NoApplicableMarginRuleException::class);

        (new RuleResolver)->resolve($branch->id, InventoryItemType::TICKET, new \DateTimeImmutable('2026-05-01'));
    }
}
