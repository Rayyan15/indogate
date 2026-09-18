<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * percent_fee is basis points (500 = 5.00%), flat_fee_minor is BIGINT
     * minor units — never float.
     */
    public function up(): void
    {
        Schema::create('payment_channel_costs', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->unique();
            $table->unsignedInteger('percent_fee');
            $table->bigInteger('flat_fee_minor');
            $table->char('currency', 3)->default('IDR');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_channel_costs');
    }
};
