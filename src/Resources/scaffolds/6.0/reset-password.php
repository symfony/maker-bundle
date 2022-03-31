<?php

use Symfony\Bundle\MakerBundle\FileManager;

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
    ],
    'configure' => function(FileManager $files) {
        $files->dumpFile(
            'config/packages/reset_password.yaml',
            file_get_contents(__DIR__.'/reset-password/config/packages/reset_password.yaml')
        );

        $login = $files->getFileContents('templates/login.html.twig');
        $forgotPassword = "</button>\n        <a class=\"btn btn-link\" href=\"{{ path('reset_password_request') }}\">Forgot your password?</a>";

        if (str_contains($login, $forgotPassword)) {
            return;
        }

        $files->dumpFile('templates/login.html.twig', str_replace(
            '</button>',
            $forgotPassword,
            $login
        ));
    },
];
