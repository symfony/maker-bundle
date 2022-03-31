<?php

use Symfony\Bundle\MakerBundle\FileManager;

return [
    'description' => 'Starting kit with authentication, registration, password reset, user profile management with an application shell styled with Bootstrap CSS.',
    'dependents' => [
        'bootstrapcss',
        'register',
        'reset-password',
        'change-password',
        'profile',
    ],
    'configure' => function(FileManager $files) {
        $files->dumpFile('templates/base.html.twig', file_get_contents(__DIR__.'/starter-kit/templates/base.html.twig'));
    },
];
