<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRD M5 gives packages no date column — a package (especially a
     * template) has no fixed departure date. duration_days is an addition
     * beyond the PRD's literal column list, needed to implement the
     * explicitly-mandated "total nights consistent with package duration"
     * validation (PRD step 7).
     */
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->json('description')->nullable();
            $table->boolean('is_template')->default(false);
            $table->unsignedInteger('base_pax')->default(2);
            $table->unsignedInteger('duration_days')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
