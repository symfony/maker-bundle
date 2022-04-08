<?php

use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;

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
    'configure' => function(FileManager $files) {
        $twig = new YamlSourceManipulator($files->getFileContents('config/packages/twig.yaml'));
        $data = $twig->getData();
        $data['twig']['form_themes'] = ['bootstrap_5_layout.html.twig'];
        $twig->setData($data);
        $files->dumpFile('config/packages/twig.yaml', $twig->getContents());

        $files->dumpFile('assets/styles/app.css', "@import \"~bootstrap/dist/css/bootstrap.css\";\n");

        $appJs = $files->getFileContents('assets/app.js');

        if (str_contains($appJs, "require('bootstrap');")) {
            return;
        }

        $files->dumpFile('assets/app.js', $appJs."require('bootstrap');\n");
    },
];
