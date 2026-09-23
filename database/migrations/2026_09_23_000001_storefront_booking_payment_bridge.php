<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Storefront (cart) bookings lost their payment path when the finance
 * migration repurposed `payments` for package_bookings. Until a real
 * payment gateway is wired in, the single manual-transfer proof lives on
 * the booking row itself. Also adds the driver_id column the model and
 * admin UI already write to, and makes customers.user_id unique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'driver_id')) {
                $table->foreignId('driver_id')->nullable()->after('customer_id')->constrained('drivers')->nullOnDelete();
            }
            $table->string('payment_proof_path')->nullable()->after('currency');
            $table->timestamp('payment_submitted_at')->nullable()->after('payment_proof_path');
            $table->foreignId('payment_verified_by')->nullable()->after('payment_submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('payment_verified_at')->nullable()->after('payment_verified_by');
            $table->text('payment_rejection_reason')->nullable()->after('payment_verified_at');
        });

        // Checkout used to create a new customer row per order. Fold
        // duplicates into the oldest row before adding the unique index.
        $dupes = DB::table('customers')->select('user_id', DB::raw('MIN(id) as keep_id'))
            ->groupBy('user_id')->havingRaw('COUNT(*) > 1')->get();

        foreach ($dupes as $dupe) {
            $others = DB::table('customers')->where('user_id', $dupe->user_id)->where('id', '!=', $dupe->keep_id)->pluck('id');
            DB::table('bookings')->whereIn('customer_id', $others)->update(['customer_id' => $dupe->keep_id]);
            DB::table('customers')->whereIn('id', $others)->delete();
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // MySQL needs an index on the FK column; recreate a plain one first.
            $table->index('user_id', 'customers_user_id_index');
            $table->dropUnique(['user_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_verified_by');
            $table->dropColumn(['payment_proof_path', 'payment_submitted_at', 'payment_verified_at', 'payment_rejection_reason']);
            $table->dropConstrainedForeignId('driver_id');
        });
    }
};
