<?php

return [

    'navigation_label' => 'Users',
    'model_label' => 'user',
    'plural_model_label' => 'users',

    'fields' => [
        'name' => 'Name',
        'email' => 'Email address',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
        'role' => 'Role',
        'is_active' => 'Active',
        'last_login' => 'Last login',
        'created_at' => 'Created at',
        'never' => 'Never',
    ],

    'validation' => [
        'cannot_remove_last_admin_role' => 'Cannot remove the Admin role from the only remaining Admin user.',
        'cannot_deactivate_last_admin' => 'Cannot deactivate the only remaining Admin user.',
    ],

];
