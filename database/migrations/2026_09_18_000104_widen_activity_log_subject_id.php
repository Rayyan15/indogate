<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bug found via MySQL (SQLite silently tolerates it, MySQL doesn't):
 * activity_log.subject_id is an unsignedBigInteger (Spatie's default
 * nullableMorphs stub), but App\Domain\Pricing\Models\Currency uses a
 * non-incrementing string primary key ('code', e.g. "KRW"). Logging any
 * Currency create/update fails outright on MySQL with "Incorrect integer
 * value" — not a seeder-only issue, this would break CurrencyForm in
 * production. Widen the column to a string so both integer and string
 * subject keys fit. No-op on SQLite, which has no real column typing to
 * fix (type affinity only, never rejects a string in an "integer" column).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $table = config('activitylog.table_name', 'activity_log');
        DB::statement("ALTER TABLE `{$table}` MODIFY `subject_id` VARCHAR(255) NULL");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $table = config('activitylog.table_name', 'activity_log');
        DB::statement("ALTER TABLE `{$table}` MODIFY `subject_id` BIGINT UNSIGNED NULL");
    }
};
