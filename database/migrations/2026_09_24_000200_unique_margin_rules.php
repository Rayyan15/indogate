<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep lowest id per (branch, product, season) before adding the unique index.
        $keep = DB::table('margin_rules')->selectRaw('MIN(id) as id')
            ->groupBy('branch_id', 'product_type', 'season_type')->pluck('id');
        DB::table('margin_rules')->whereNotIn('id', $keep)->delete();

        Schema::table('margin_rules', function (Blueprint $table) {
            $table->unique(['branch_id', 'product_type', 'season_type'], 'margin_rules_unique_scope');
        });
    }

    public function down(): void
    {
        Schema::table('margin_rules', function (Blueprint $table) {
            $table->dropUnique('margin_rules_unique_scope');
        });
    }
};
