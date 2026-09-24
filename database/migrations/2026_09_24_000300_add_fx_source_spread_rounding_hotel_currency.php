<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->string('source', 40)->default('manual')->after('rate');
            $table->dateTime('pinned_until')->nullable()->after('source');
        });
        // Existing system rows (fx:fetch) had created_by = null.
        DB::table('exchange_rates')->whereNull('created_by')->update(['source' => 'api:fawazahmed0']);

        Schema::table('currencies', function (Blueprint $table) {
            // Basis points added to the display price (150 = 1.5%).
            $table->unsignedInteger('spread_bps')->default(0);
            // Display prices round UP to a multiple of this many major units; 0 = off.
            $table->unsignedInteger('display_rounding')->default(0);
        });

        Schema::table('hotels', function (Blueprint $table) {
            // Legacy prices were entered as IDR.
            $table->string('currency', 3)->default('IDR');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', fn (Blueprint $t) => $t->dropColumn('currency'));
        Schema::table('currencies', fn (Blueprint $t) => $t->dropColumn(['spread_bps', 'display_rounding']));
        Schema::table('exchange_rates', fn (Blueprint $t) => $t->dropColumn(['source', 'pinned_until']));
    }
};
