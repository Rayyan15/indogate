<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRD wants table "pricing_rules" but that name is taken by the old MVP
     * schema (service_type/season_start/season_end/markup_percent) still in
     * use by admin/pricing. New PRD schema lives here as margin_rules.
     * margin_percent is basis points (2500 = 25.00%), never float.
     */
    public function up(): void
    {
        Schema::create('margin_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('product_type');
            $table->string('season_type')->nullable();
            $table->unsignedInteger('margin_percent');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'product_type', 'season_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('margin_rules');
    }
};
