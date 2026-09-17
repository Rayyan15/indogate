<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * cost_minor is BIGINT, no float — rule.md money rule applies from
     * the first migration that stores a price, not just from M4 onward.
     */
    public function up(): void
    {
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->date('valid_from');
            $table->date('valid_to');
            $table->bigInteger('cost_minor');
            $table->char('currency', 3)->default('IDR');
            $table->timestamps();

            $table->index(['inventory_item_id', 'valid_from', 'valid_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rates');
    }
};
