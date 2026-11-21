<?php

return [
    'mailserver_key' => env('SENDZLY_MAILSERVER_KEY', ''),
    'campaign_key' => env('SENDZLY_CAMPAIGN_KEY', ''),
    'base_url' => env('SENDZLY_BASE_URL', 'https://sendzly.com/api/v1'),
    'timeout' => (int) env('SENDZLY_TIMEOUT', 15),
];
