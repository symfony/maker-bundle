<?php

return [
    'description' => 'Reset password system using symfonycasts/reset-password-bundle with tests.',
    'dependents' => [
        'auth',
    ],
    'packages' => [
        'form' => 'all',
        'validator' => 'all',
        'mailer' => 'all',
        'symfonycasts/reset-password-bundle' => 'all',
        'zenstruck/mailer-test' => 'dev',
    ]
];
