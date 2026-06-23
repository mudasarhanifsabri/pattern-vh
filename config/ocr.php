<?php

return [
    'enabled' => env('OCR_ENABLED', true),
    'provider' => env('OCR_PROVIDER', 'textract'),
    'textract_mode' => env('OCR_TEXTRACT_MODE', 'detect_text'),
    'text_fallback' => env('OCR_TEXT_FALLBACK', false),
    'aws' => [
        'key' => env('OCR_AWS_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
        'secret' => env('OCR_AWS_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
        'region' => env('OCR_AWS_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
    ],
];
