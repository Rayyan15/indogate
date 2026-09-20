<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            if (! Schema::hasColumn('packages', 'is_published')) {
                $table->boolean('is_published')->default(true)->after('is_template');
            }
            if (! Schema::hasColumn('packages', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_published');
            }
            if (! Schema::hasColumn('packages', 'cover_image')) {
                $table->string('cover_image')->nullable()->after('brochure_path');
            }
            if (! Schema::hasColumn('packages', 'highlights')) {
                $table->json('highlights')->nullable()->after('cover_image');
            }
            if (! Schema::hasColumn('packages', 'starting_price_idr')) {
                $table->unsignedBigInteger('starting_price_idr')->nullable()->after('highlights');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $columns = [];
            foreach (['is_published', 'is_featured', 'cover_image', 'highlights', 'starting_price_idr'] as $col) {
                if (Schema::hasColumn('packages', $col)) {
                    $columns[] = $col;
                }
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
