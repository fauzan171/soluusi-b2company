<?php

return [
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'is3ds' => env('MIDTRANS_IS_3DS'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
    // Dummy flow: local env only, atau flag eksplisit untuk staging demo.
    'dummy_enabled' => env('MIDTRANS_DUMMY_ENABLED', false),
];
