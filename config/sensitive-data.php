<?php

return [
    'key' => env('SENSITIVE_DATA_KEY'),
    'previous_keys' => array_filter(explode(',', env('SENSITIVE_DATA_PREVIOUS_KEYS', ''))),
];
