<?php

return [
    'enabled' => env('PAYMENT_ASYNC_ENABLED', false),
    'queue' => 'payments',
];
