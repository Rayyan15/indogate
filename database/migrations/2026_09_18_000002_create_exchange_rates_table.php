<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Insert-only: never updated. "Current rate" = latest row where
     * effective_from <= now(). rate is decimal (string in PHP), never float.
     */
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->char('currency', 3);
            $table->decimal('rate', 20, 8);
            $table->dateTime('effective_from');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['currency', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
