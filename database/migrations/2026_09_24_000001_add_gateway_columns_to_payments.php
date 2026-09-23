<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_intents', function (Blueprint $table) {
            $table->string('provider', 30)->default('manual_transfer')->after('booking_id');
            $table->string('public_token', 64)->nullable()->unique()->after('provider');
            $table->string('payment_type', 30)->default('down_payment')->after('channel');
            $table->string('method', 30)->nullable()->after('payment_type');
            $table->string('provider_reference')->nullable()->after('status');
            $table->timestamp('paid_at')->nullable()->after('expires_at');
            $table->string('failure_reason')->nullable()->after('paid_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('source', 20)->default('manual')->after('channel');
            $table->string('provider_reference')->nullable()->unique()->after('source');
        });

        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('event_id', 100);
            $table->json('payload');
            $table->boolean('signature_valid');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['provider_reference']);
            $table->dropColumn(['source', 'provider_reference']);
        });

        Schema::table('payment_intents', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn(['provider', 'public_token', 'payment_type', 'method', 'provider_reference', 'paid_at', 'failure_reason']);
        });
    }
};
