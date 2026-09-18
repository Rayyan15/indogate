<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_id')->constrained();
            $table->string('code', 20)->unique();
            $table->string('status')->default('confirmed');
            $table->date('departure_date');
            $table->date('return_date')->nullable();
            $table->bigInteger('total_minor');
            $table->string('currency', 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_bookings');
    }
};
