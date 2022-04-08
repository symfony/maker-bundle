<?php

use Symfony\Bundle\MakerBundle\FileManager;

return [
    'description' => 'Create login form and tests.',
    'dependents' => [
        'user',
        'homepage',
    ],
    'packages' => [
        'symfony/web-profiler-bundle' => 'dev',
        'symfony/stopwatch' => 'dev',
    ],
    'configure' => function(FileManager $files) {
        // add LoginUser service
        $files->manipulateYaml('config/services.yaml', function(array $data) {
            $data['services']['App\Security\LoginUser']['$authenticator'] ='@security.authenticator.form_login.main';

            return $data;
        });

        // make security.yaml adjustments
        $files->manipulateYaml('config/packages/security.yaml', function(array $data) {
            $data['security']['providers'] = [
                'app_user_provider' => [
                    'entity' => [
                        'class' => 'App\Entity\User',
                        'property' => 'email',
                    ],
                ],
            ];
            $data['security']['firewalls']['main'] = [
                'lazy' => true,
                'provider' => 'app_user_provider',
                'form_login' => [
                    'login_path' => 'login',
                    'check_path' => 'login',
                    'username_parameter' => 'email',
                    'password_parameter' => 'password',
                    'enable_csrf' => true,
                ],
                'logout' => [
                    'path' => 'logout',
                    'target' => 'homepage',
                ],
                'remember_me' => [
                    'secret' => '%kernel.secret%',
                    'secure' => 'auto',
                    'samesite' => 'lax',
                ],
            ];

            return $data;
        });
    },
];
