<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seeded Admin User
    |--------------------------------------------------------------------------
    |
    | Credentials used by RolesAndPermissionsSeeder to create the initial
    | Admin user able to authenticate into the Filament admin panel.
    |
    */

    'seed_email' => env('ADMIN_EMAIL', 'admin@example.com'),

    'seed_password' => env('ADMIN_PASSWORD', 'password'),

];
