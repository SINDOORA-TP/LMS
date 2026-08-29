<?php

// PhonePe configuration (add this to your config/services.php)
// Since we're pre-writing files before Laravel install, this is a standalone config.

return [
    'phonepe' => [
        'merchant_id' => env('PHONEPE_MERCHANT_ID', ''),
        'salt_key' => env('PHONEPE_SALT_KEY', ''),
        'salt_index' => env('PHONEPE_SALT_INDEX', 1),
        'env' => env('PHONEPE_ENV', 'UAT'),
    ],
];
