<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('package_bookings')->cascadeOnDelete();
            $table->string('name');
            $table->text('passport_number')->nullable();
            $table->string('passport_file')->nullable();
            $table->string('nationality')->nullable();
            $table->boolean('is_lead_guest')->default(false);
            $table->timestamps();

            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_guests');
    }
};
