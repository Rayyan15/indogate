<?php

$migrationsDir = __DIR__ . '/database/migrations/';
$files = scandir($migrationsDir);

$schemas = [
    'create_users_table' => function($content) {
        // Add phone and two_factor_secret to users table
        return str_replace(
            "\$table->string('email')->unique();",
            "\$table->string('email')->unique();\n            \$table->string('phone')->nullable();\n            \$table->text('two_factor_secret')->nullable();\n            \$table->text('two_factor_recovery_codes')->nullable();",
            $content
        );
    },
    'create_customers_table' => function($content) {
        $schema = <<<PHP
\$table->id();
            \$table->foreignId('user_id')->constrained()->onDelete('cascade');
            \$table->string('full_name');
            \$table->string('nationality')->nullable();
            \$table->text('passport_number')->nullable();
            \$table->timestamps();
            \$table->softDeletes();
PHP;
        return preg_replace('/\$table->id\(\);\n            \$table->timestamps\(\);/', $schema, $content);
    },
    'create_hotels_table' => function($content) {
        $schema = <<<PHP
\$table->id();
            \$table->json('name');
            \$table->json('description')->nullable();
            \$table->integer('base_price')->default(0);
            \$table->string('location')->nullable();
            \$table->boolean('is_active')->default(true);
            \$table->timestamps();
            \$table->softDeletes();
PHP;
        return preg_replace('/\$table->id\(\);\n            \$table->timestamps\(\);/', $schema, $content);
    },
    'create_hotel_rooms_table' => function($content) {
        $schema = <<<PHP
\$table->id();
            \$table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            \$table->string('room_type');
            \$table->integer('capacity')->default(2);
            \$table->integer('base_price')->default(0);
            \$table->timestamps();
            \$table->softDeletes();
PHP;
        return preg_replace('/\$table->id\(\);\n            \$table->timestamps\(\);/', $schema, $content);
    },
    'create_flight_routes_table' => function($content) {
        $schema = <<<PHP
\$table->id();
            \$table->string('airline');
            \$table->string('origin');
            \$table->string('destination');
            \$table->dateTime('departure_at');
            \$table->integer('base_price')->default(0);
            \$table->integer('seat_quota')->default(0);
            \$table->timestamps();
            \$table->softDeletes();
PHP;
        return preg_replace('/\$table->id\(\);\n            \$table->timestamps\(\);/', $schema, $content);
    },
    'create_drivers_table' => function($content) {
        $schema = <<<PHP
\$table->id();
            \$table->string('full_name');
            \$table->string('gender')->default('male');
            \$table->string('phone')->nullable();
            \$table->boolean('is_active')->default(true);
            \$table->timestamps();
            \$table->softDeletes();
PHP;
        return preg_replace('/\$table->id\(\);\n            \$table->timestamps\(\);/', $schema, $content);
    },
    'create_vehicles_table' => function($content) {
        $schema = <<<PHP
\$table->id();
            \$table->foreignId('driver_id')->constrained()->onDelete('cascade');
            \$table->string('type');
            \$table->string('plate_number');
            \$table->integer('capacity')->default(4);
            \$table->timestamps();
            \$table->softDeletes();
PHP;
        return preg_replace('/\$table->id\(\);\n            \$table->timestamps\(\);/', $schema, $content);
    },
    'create_tour_addons_table' => function($content) {
        $schema = <<<PHP
\$table->id();
            \$table->json('name');
            \$table->integer('price')->default(0);
            \$table->timestamps();
            \$table->softDeletes();
PHP;
        return preg_replace('/\$table->id\(\);\n            \$table->timestamps\(\);/', $schema, $content);
    },
    'create_bookings_table' => function($content) {
        $schema = <<<PHP
\$table->id();
            \$table->uuid('booking_number')->unique();
            \$table->foreignId('customer_id')->constrained()->onDelete('cascade');
            \$table->string('status')->default('pending_payment');
            \$table->integer('total_amount')->default(0);
            \$table->string('currency')->default('IDR');
            \$table->timestamps();
            \$table->softDeletes();
PHP;
        return preg_replace('/\$table->id\(\);\n            \$table->timestamps\(\);/', $schema, $content);
    },
    'create_booking_items_table' => function($content) {
        $schema = <<<PHP
\$table->id();
            \$table->foreignId('booking_id')->constrained()->onDelete('cascade');
            \$table->morphs('bookable');
            \$table->integer('quantity')->default(1);
            \$table->integer('unit_price')->default(0);
            \$table->integer('subtotal')->default(0);
            \$table->timestamps();
            \$table->softDeletes();
PHP;
        return preg_replace('/\$table->id\(\);\n            \$table->timestamps\(\);/', $schema, $content);
    },
    'create_payments_table' => function($content) {
        $schema = <<<PHP
\$table->id();
            \$table->foreignId('booking_id')->constrained()->onDelete('cascade');
            \$table->integer('amount')->default(0);
            \$table->string('status')->default('pending');
            \$table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            \$table->dateTime('verified_at')->nullable();
            \$table->timestamps();
            \$table->softDeletes();
PHP;
        return preg_replace('/\$table->id\(\);\n            \$table->timestamps\(\);/', $schema, $content);
    },
    'create_payment_proofs_table' => function($content) {
        $schema = <<<PHP
\$table->id();
            \$table->foreignId('payment_id')->constrained()->onDelete('cascade');
            \$table->string('file_path');
            \$table->dateTime('uploaded_at');
            \$table->timestamps();
            \$table->softDeletes();
PHP;
        return preg_replace('/\$table->id\(\);\n            \$table->timestamps\(\);/', $schema, $content);
    },
    'create_pricing_rules_table' => function($content) {
        $schema = <<<PHP
\$table->id();
            \$table->string('service_type');
            \$table->date('season_start');
            \$table->date('season_end');
            \$table->decimal('markup_percent', 5, 2)->default(0);
            \$table->string('tier')->nullable();
            \$table->timestamps();
            \$table->softDeletes();
PHP;
        return preg_replace('/\$table->id\(\);\n            \$table->timestamps\(\);/', $schema, $content);
    }
];

foreach ($files as $file) {
    if (str_ends_with($file, '.php')) {
        foreach ($schemas as $suffix => $callback) {
            if (str_contains($file, $suffix)) {
                $path = $migrationsDir . $file;
                $content = file_get_contents($path);
                $newContent = $callback($content);
                file_put_contents($path, $newContent);
                echo "Updated: $file\n";
            }
        }
    }
}
echo "Migrations updated.\n";
