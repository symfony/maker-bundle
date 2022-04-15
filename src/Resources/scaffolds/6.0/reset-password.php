<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Bundle\MakerBundle\FileManager;

return [
    'description' => 'Reset password system using symfonycasts/reset-password-bundle with tests.',
    'dependents' => [
        'auth',
    ],
    'packages' => [
        'symfony/form' => 'all',
        'symfony/validator' => 'all',
        'symfony/mailer' => 'all',
        'symfonycasts/reset-password-bundle' => 'all',
        'zenstruck/mailer-test' => 'dev',
    ],
    'configure' => function (FileManager $files) {
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
