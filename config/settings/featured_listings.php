<?php
/**
 * Featured Listings Configuration
 * Pricing and duration packages for featured listings
 */

return [
    'packages' => [
        '14' => [
            'days' => 14,
            'price' => 500.00,
            'label' => '2 Weeks',
            'description' => 'Feature your listing for 2 weeks',
            'savings' => null
        ],
        '30' => [
            'days' => 30,
            'price' => 1000.00,
            'label' => '1 Month',
            'description' => 'Feature your listing for 1 month',
            'savings' => 'Best Value - Save GH₵'
        ]
    ],

    'benefits' => [
        'Appear at the top of search results',
        'Get highlighted with a special badge',
        'Increase visibility by up to 5x',
        'Attract more potential buyers'
    ],

    'enabled' => true, // Set to false to disable featured listings platform-wide
    'max_featured_per_user' => 5, // Maximum number of active featured listings per vendor
];
