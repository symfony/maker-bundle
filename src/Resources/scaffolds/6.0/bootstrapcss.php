<?php

use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;

return [
    'description' => 'Add bootstrap css/js.',
    'packages' => [
        'twig' => 'all',
        'encore' => 'all',
    ],
    'configure' => function(FileManager $files) {
        $packageJson = json_decode($files->getFileContents('package.json'), true);
        $devDeps = $packageJson['devDependencies'];
        $devDeps['bootstrap'] = '^5.0.0';
        $devDeps['@popperjs/core'] = '^2.0.0';

        ksort($devDeps);

        $packageJson['devDependencies'] = $devDeps;
        $files->dumpFile('package.json', json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

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
