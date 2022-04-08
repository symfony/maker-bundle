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
    'description' => 'Add bootstrap css/js.',
    'packages' => [
        'symfony/twig-bundle' => 'all',
        'symfony/webpack-encore-bundle' => 'all',
    ],
    'js_packages' => [
        'bootstrap' => '^5.0.0',
        '@popperjs/core' => '^2.0.0',
    ],
    'configure' => function (FileManager $files) {
        // add bootstrap form theme
        $files->manipulateYaml('config/packages/twig.yaml', function (array $data) {
            $data['twig']['form_themes'] = ['bootstrap_5_layout.html.twig'];

            return $data;
        });

        // add bootstrap to app.css
        $files->dumpFile('assets/styles/app.css', "@import \"~bootstrap/dist/css/bootstrap.css\";\n");

        // add bootstrap to app.js
        $appJs = $files->getFileContents('assets/app.js');

        if (str_contains($appJs, "require('bootstrap');")) {
            return;
        }

        $files->dumpFile('assets/app.js', $appJs."require('bootstrap');\n");
    },
];
