<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bug-review DL-07/08/09/12, BF-08/11/18:
 *  - deleting a branch, booking or partner no longer silently wipes the
 *    finance ledger (cascade -> restrict); branches are deactivated instead
 *  - one booking per quotation
 *  - legacy 'manual_transfer' channel rows moved to the enum value
 *  - vendor payments soft-delete instead of hard delete
 */
return new class extends Migration
{
    /** @var array<string, array<int, string>> table => FK columns to restrict */
    private array $restrict = [
        'partners' => ['branch_id'],
        'inventory_items' => ['branch_id'],
        'packages' => ['branch_id'],
        'leads' => ['branch_id'],
        'quotations' => ['branch_id'],
        'package_bookings' => ['branch_id'],
        'driver_assignments' => ['branch_id'],
        'payment_intents' => ['branch_id', 'booking_id'],
        'payments' => ['branch_id', 'booking_id'],
        'refunds' => ['branch_id', 'booking_id'],
        'vendor_payments' => ['branch_id', 'partner_id'],
    ];

    public function up(): void
    {
        $this->swapForeignKeys('restrict');

        Schema::table('package_bookings', fn (Blueprint $t) => $t->unique('quotation_id'));

        Schema::table('vendor_payments', fn (Blueprint $t) => $t->softDeletes());

        foreach (['payments', 'payment_intents'] as $table) {
            DB::table($table)->where('channel', 'manual_transfer')->update(['channel' => 'bank_transfer']);
            Schema::table($table, fn (Blueprint $t) => $t->string('channel', 50)->default('bank_transfer')->change());
        }
    }

    public function down(): void
    {
        foreach (['payments', 'payment_intents'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->string('channel', 50)->default('manual_transfer')->change());
        }

        Schema::table('vendor_payments', fn (Blueprint $t) => $t->dropSoftDeletes());

        // MySQL keeps an index on FK columns; add a plain one before dropping the unique.
        Schema::table('package_bookings', function (Blueprint $t) {
            $t->index('quotation_id');
            $t->dropUnique(['quotation_id']);
        });

        $this->swapForeignKeys('cascade');
    }

    private function swapForeignKeys(string $onDelete): void
    {
        foreach ($this->restrict as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($columns, $onDelete) {
                foreach ($columns as $column) {
                    $t->dropForeign([$column]);

                    $references = match ($column) {
                        'branch_id' => 'branches',
                        'booking_id' => 'package_bookings',
                        'partner_id' => 'partners',
                    };

                    $t->foreign($column)->references('id')->on($references)->onDelete($onDelete);
                }
            });
        }
    }
};
