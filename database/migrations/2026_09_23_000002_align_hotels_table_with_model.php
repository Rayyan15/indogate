<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hotel model, admin HotelController, storefront search and cart all use
 * star_rating + base_price_per_night; the table only had base_price, so
 * creating a hotel failed with "Unknown column".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->renameColumn('base_price', 'base_price_per_night');
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->unsignedTinyInteger('star_rating')->default(3)->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn('star_rating');
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->renameColumn('base_price_per_night', 'base_price');
        });
    }
};
