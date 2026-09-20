<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            if (! Schema::hasColumn('drivers', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (! Schema::hasColumn('drivers', 'languages')) {
                $table->json('languages')->nullable()->after('phone');
            }
        });

        // Backfill name from full_name if full_name exists
        if (Schema::hasColumn('drivers', 'full_name') && Schema::hasColumn('drivers', 'name')) {
            DB::table('drivers')->whereNull('name')->update([
                'name' => DB::raw('full_name'),
            ]);
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'plate')) {
                $table->string('plate')->nullable()->after('id');
            }
            if (! Schema::hasColumn('vehicles', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('capacity');
            }
        });

        if (Schema::hasColumn('vehicles', 'plate_number') && Schema::hasColumn('vehicles', 'plate')) {
            DB::table('vehicles')->whereNull('plate')->update([
                'plate' => DB::raw('plate_number'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['name', 'languages']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['plate', 'is_active']);
        });
    }
};
