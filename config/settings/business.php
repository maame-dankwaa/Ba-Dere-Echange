<?php
/**
 * Business Configuration
 * Platform commission and fee settings
 */

return [
    'commission' => [
        // Default platform commission rate (10%)
        'rate' => 0.10,

        // Vendor-specific commission rate (if different)
        'vendor_rate' => 0.10,

        // Institution-specific commission rate (if different)
        'institution_rate' => 0.08,
    ],

    'fees' => [
        // Minimum transaction amount
        'min_transaction' => 0,

        // Maximum transaction amount (0 = no limit)
        'max_transaction' => 0,
    ],
];
