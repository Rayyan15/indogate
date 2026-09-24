<?php

namespace Tests\Feature;

use App\Domain\Pricing\Models\MarginRule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PriorityOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_margin_rules_scope_is_unique(): void
    {
        $this->assertTrue(Schema::hasIndex('margin_rules', 'margin_rules_unique_scope'));
    }

    public function test_margin_rule_branch_relation_resolves(): void
    {
        $this->assertInstanceOf(BelongsTo::class, (new MarginRule)->branch());
    }
}
