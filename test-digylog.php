<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "Testing EXACT User syntax:\n";
$tracking = 'SFC64A27A';
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.digylog.token'),
    'Referer' => 'https://apiseller.digylog.com',
    'Accept' => 'application/json',
])->get('https://api.digylog.com/api/v2/seller/historics?' . http_build_query([
    'trakings' => $tracking
]));

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n\n";
