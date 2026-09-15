<?php

$enumsDir = __DIR__ . '/app/Enums';
if (!is_dir($enumsDir)) {
    mkdir($enumsDir, 0755, true);
}

$enums = [
    'BookingStatus' => [
        'PENDING_PAYMENT' => 'pending_payment',
        'AWAITING_VERIFICATION' => 'awaiting_verification',
        'CONFIRMED' => 'confirmed',
        'COMPLETED' => 'completed',
        'CANCELLED' => 'cancelled',
        'REFUNDED' => 'refunded',
    ],
    'PaymentStatus' => [
        'PENDING' => 'pending',
        'SUBMITTED' => 'submitted',
        'VERIFIED' => 'verified',
        'REJECTED' => 'rejected',
    ],
    'DriverGenderPref' => [
        'MALE' => 'male',
        'FEMALE' => 'female',
        'NO_PREFERENCE' => 'no_preference',
    ],
    'UserRole' => [
        'CUSTOMER' => 'customer',
        'CS_ADMIN' => 'cs_admin',
        'FINANCE_ADMIN' => 'finance_admin',
        'SUPER_ADMIN' => 'super_admin',
    ],
    'Locale' => [
        'ID' => 'id',
        'EN' => 'en',
        'AR' => 'ar',
    ]
];

foreach ($enums as $name => $cases) {
    $content = "<?php\n\nnamespace App\Enums;\n\nenum {$name}: string\n{\n";
    foreach ($cases as $key => $val) {
        $content .= "    case {$key} = '{$val}';\n";
    }
    $content .= "}\n";
    file_put_contents("{$enumsDir}/{$name}.php", $content);
}
echo "Enums created.\n";
