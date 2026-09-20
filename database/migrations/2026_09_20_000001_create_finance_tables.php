<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add created_by to package_bookings if not present
        if (Schema::hasTable('package_bookings') && ! Schema::hasColumn('package_bookings', 'created_by')) {
            Schema::table('package_bookings', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->after('branch_id')->constrained('users')->nullOnDelete();
            });
        }

        // 2. Drop old empty stub tables if present
        if (Schema::hasTable('payment_proofs')) {
            Schema::dropIfExists('payment_proofs');
        }
        if (Schema::hasTable('payments')) {
            Schema::dropIfExists('payments');
        }

        // 3. Create payment_intents table
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('package_bookings')->cascadeOnDelete();
            $table->string('channel', 50)->default('manual_transfer'); // manual_transfer, credit_card, etc.
            $table->bigInteger('amount_minor');
            $table->string('currency', 3)->default('IDR');
            $table->decimal('fx_rate', 16, 8)->default(1.00000000);
            $table->bigInteger('channel_fee_minor')->default(0);
            $table->string('status', 30)->default('pending'); // pending, completed, cancelled, expired
            $table->text('notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index('booking_id');
        });

        // 4. Create modern payments table
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('package_bookings')->cascadeOnDelete();
            $table->foreignId('payment_intent_id')->nullable()->constrained('payment_intents')->nullOnDelete();
            $table->string('type', 30)->default('down_payment'); // down_payment, full_payment, installment
            $table->bigInteger('amount_minor');
            $table->string('currency', 3)->default('IDR');
            $table->decimal('fx_rate', 16, 8)->default(1.00000000);
            $table->bigInteger('idr_equivalent_minor');
            $table->bigInteger('channel_fee_minor')->default(0);
            $table->string('proof_file')->nullable();
            $table->string('channel', 50)->default('manual_transfer');
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('pending'); // pending, verified, rejected
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index(['booking_id', 'status']);
        });

        // 5. Create vendor_payments table
        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('package_bookings')->nullOnDelete();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->bigInteger('amount_minor');
            $table->string('currency', 3)->default('IDR');
            $table->decimal('fx_rate', 16, 8)->default(1.00000000);
            $table->bigInteger('idr_equivalent_minor');
            $table->string('description')->nullable();
            $table->string('proof_file')->nullable();
            $table->date('paid_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'partner_id']);
            $table->index('booking_id');
        });

        // 6. Create refunds table
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('package_bookings')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->bigInteger('amount_minor');
            $table->string('currency', 3)->default('IDR');
            $table->decimal('fx_rate', 16, 8)->default(1.00000000);
            $table->bigInteger('idr_equivalent_minor');
            $table->text('reason'); // Wajib per PRD
            $table->foreignId('processed_by')->constrained('users');
            $table->string('status', 30)->default('completed'); // pending, completed
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'booking_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('vendor_payments');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_intents');

        if (Schema::hasTable('package_bookings') && Schema::hasColumn('package_bookings', 'created_by')) {
            Schema::table('package_bookings', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }
    }
};
