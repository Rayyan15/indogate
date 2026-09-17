<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every table transacting money or capacity is branch-scoped from this
     * migration onward, per PRD §1.2: "Tidak bisa ditambahkan belakangan
     * tanpa migrasi data besar." Existing rows backfill to the first
     * active branch (fresh/dev data only).
     */
    private array $tables = [
        'hotels', 'hotel_rooms', 'flight_routes', 'vehicles',
        'drivers', 'bookings', 'payments', 'pricing_rules',
    ];

    public function up(): void
    {
        $defaultBranchId = DB::table('branches')->orderBy('id')->value('id');

        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('branch_id')->nullable()->after('id')->constrained();
            });

            if ($defaultBranchId !== null) {
                DB::table($table)->update(['branch_id' => $defaultBranchId]);
            }
        }

        // Column stays nullable at the schema level (avoids a doctrine/dbal
        // dependency for ALTER ... MODIFY across drivers); non-null is
        // guaranteed in practice by BelongsToBranch::bootBelongsToBranch()
        // auto-filling branch_id from the active branch on every create.
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
