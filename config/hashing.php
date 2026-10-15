<?php

// PRESENTATION NOTE: hashing configuration.
// This file returns settings that Laravel reads when booting or using this service.
// Environment variables keep deployment-specific values outside the application code.

return [
    // Select the driver implementation used by Laravel.
    'driver' => 'bcrypt',
    // Configure the bcrypt option for this Laravel component.
    'bcrypt' => ['rounds' => env('BCRYPT_ROUNDS', 10)],
    // Configure the argon option for this Laravel component.
    'argon' => ['memory' => 65536, 'threads' => 1, 'time' => 4],
];
