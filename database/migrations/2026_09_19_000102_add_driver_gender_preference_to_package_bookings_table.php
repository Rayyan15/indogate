<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('package_bookings', 'driver_gender_preference')) {
                $table->string('driver_gender_preference', 20)->nullable()->after('currency');
            }
        });

        if (Schema::hasTable('bookings') && ! Schema::hasColumn('bookings', 'driver_gender_preference')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('driver_gender_preference', 20)->nullable();
            });
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'driver_id')) {
                $table->foreignId('driver_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('package_bookings', function (Blueprint $table) {
            $table->dropColumn('driver_gender_preference');
        });

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'driver_gender_preference')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('driver_gender_preference');
            });
        }
    }
};
