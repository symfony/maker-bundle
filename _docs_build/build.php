#!/usr/bin/env php
<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use SymfonyDocsBuilder\BuildConfig;
use SymfonyDocsBuilder\DocBuilder;

(new Application('Symfony Docs Builder', '1.0'))
    ->register('build-docs')
    ->addOption('generate-fjson-files', null, InputOption::VALUE_NONE, 'Use this option to generate docs both in HTML and JSON formats')
    ->setCode(function (InputInterface $input, OutputInterface $output) {
        $io = new SymfonyStyle($input, $output);
        $io->text('Building all Symfony Docs...');

        $outputDir = __DIR__.'/output';
        $buildConfig = (new BuildConfig())
            ->setSymfonyVersion('7.1')
            ->setContentDir(__DIR__.'/../docs')
            ->setOutputDir($outputDir)
            ->setImagesDir(__DIR__.'/output/_images')
            ->setImagesPublicPrefix('_images')
            ->setTheme('rtd')
            ->diableBuildCache()
        ;

        $buildConfig->setExcludedPaths(['.github/', '_build/']);

        if (!$generateJsonFiles = $input->getOption('generate-fjson-files')) {
            $buildConfig->disableJsonFileGeneration();
        }

        $io->comment(sprintf('cache: disabled / output file type(s): %s', $generateJsonFiles ? 'HTML and JSON' : 'HTML'));

        $result = (new DocBuilder())->build($buildConfig);

        if ($result->isSuccessful()) {
            $io->success(sprintf('The Symfony Docs were successfully built at %s', realpath($outputDir)));

            return 0;
        }

        $io->error(sprintf("There were some errors while building the docs:\n\n%s\n", $result->getErrorTrace()));
        $io->newLine();
        $io->comment('Tip: you can add the -v, -vv or -vvv flags to this command to get debug information.');

        return 1;
    })
    ->getApplication()
    ->setDefaultCommand('build-docs', true)
    ->run();
