<?php

return [
    'enabled' => env('OCR_ENABLED', true),
    'provider' => env('OCR_PROVIDER', 'textract'),
    'aws' => [
        'key' => env('OCR_AWS_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
        'secret' => env('OCR_AWS_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
        'region' => env('OCR_AWS_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
    ],
];
