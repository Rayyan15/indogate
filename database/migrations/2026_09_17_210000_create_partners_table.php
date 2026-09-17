<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRD M3. branch_id lives in the CREATE migration from day one —
     * rule.md is explicit the M1 retrofit (add_branch_id_to_transactional_tables)
     * was a one-time MVP exception, never to repeat.
     */
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->string('type'); // App\Enums\PartnerType: hotel|villa|vehicle_vendor
            $table->string('city')->nullable();
            $table->string('contact')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
