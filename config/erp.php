<?php

return [
    'vat_rate' => (float) env('VAT_RATE', 5),
    'web_updater_enabled' => (bool) env('WEB_UPDATER_ENABLED', true),
    'git_binary' => env('GIT_BINARY', 'git'),
    'composer_binary' => env('COMPOSER_BINARY', 'composer'),
    'composer_home' => env('COMPOSER_HOME', storage_path('app/composer')),
    'npm_binary' => env('NPM_BINARY', 'npm'),
];
