<?php

return [
    'vat_rate' => (float) env('VAT_RATE', 5),
    'web_updater_enabled' => (bool) env('WEB_UPDATER_ENABLED', true),
    'composer_binary' => env('COMPOSER_BINARY', 'composer'),
    'npm_binary' => env('NPM_BINARY', 'npm'),
];
