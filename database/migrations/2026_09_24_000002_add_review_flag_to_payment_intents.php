<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_intents', function (Blueprint $table) {
            $table->timestamp('needs_review_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('payment_intents', function (Blueprint $table) {
            $table->dropColumn('needs_review_at');
        });
    }
};
