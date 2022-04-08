<?php

use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;

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
        $services = new YamlSourceManipulator($files->getFileContents('config/services.yaml'));
        $data = $services->getData();
        $data['services']['App\Security\LoginUser']['$authenticator'] ='@security.authenticator.form_login.main';
        $services->setData($data);
        $files->dumpFile('config/services.yaml', $services->getContents());

        $security = new YamlSourceManipulator($files->getFileContents('config/packages/security.yaml'));
        $data = $security->getData();
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
        $security->setData($data);
        $files->dumpFile('config/packages/security.yaml', $security->getContents());
    },
];
