<?php

namespace Tests\Unit;

use App\Domain\Pricing\Models\Season;
use App\Domain\Pricing\Rules\SeasonDoesNotOverlap;
use App\Enums\SeasonType;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonOverlapTest extends TestCase
{
    use RefreshDatabase;

    public function test_overlapping_season_is_rejected(): void
    {
        $branch = Branch::create(['name' => 'Bali', 'code' => 'BALI', 'timezone' => 'Asia/Makassar', 'is_active' => true]);

        Season::withoutGlobalScopes()->create(['branch_id' => $branch->id, 'name' => 'Peak', 'date_from' => '2026-12-01', 'date_to' => '2026-12-31', 'type' => SeasonType::PEAK]);

        $rule = new SeasonDoesNotOverlap($branch->id, '2026-12-15');
        $failed = null;
        $rule->validate('date_to', '2027-01-15', function ($message) use (&$failed) {
            $failed = $message;
        });

        $this->assertNotNull($failed);
    }

    public function test_non_overlapping_season_is_accepted(): void
    {
        $branch = Branch::create(['name' => 'Bali', 'code' => 'BALI', 'timezone' => 'Asia/Makassar', 'is_active' => true]);

        Season::withoutGlobalScopes()->create(['branch_id' => $branch->id, 'name' => 'Peak', 'date_from' => '2026-12-01', 'date_to' => '2026-12-31', 'type' => SeasonType::PEAK]);

        $rule = new SeasonDoesNotOverlap($branch->id, '2027-01-01');
        $failed = null;
        $rule->validate('date_to', '2027-01-31', function ($message) use (&$failed) {
            $failed = $message;
        });

        $this->assertNull($failed);
    }

    public function test_editing_a_season_ignores_itself_when_checking_overlap(): void
    {
        $branch = Branch::create(['name' => 'Bali', 'code' => 'BALI', 'timezone' => 'Asia/Makassar', 'is_active' => true]);

        $season = Season::withoutGlobalScopes()->create(['branch_id' => $branch->id, 'name' => 'Peak', 'date_from' => '2026-12-01', 'date_to' => '2026-12-31', 'type' => SeasonType::PEAK]);

        $rule = new SeasonDoesNotOverlap($branch->id, '2026-12-01', $season->id);
        $failed = null;
        $rule->validate('date_to', '2026-12-31', function ($message) use (&$failed) {
            $failed = $message;
        });

        $this->assertNull($failed);
    }
}
