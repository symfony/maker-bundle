<?php

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeController;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeControllerTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'controller_basic' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                'FooBar',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeController')
            ->assert(function (string $output, string $directory) {
                // make sure the template was not configured
                $this->assertContainsCount('created: ', $output, 1);
            }),
        ];

        yield 'controller_with_template_and_base' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                'FooTwig',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeControllerTwig')
            ->addExtraDependencies('twig'),
        ];

        yield 'controller_with_template_no_base' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                'FooTwig',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeControllerTwig')
            ->addExtraDependencies('twig')
            ->deleteFile('templates/base.html.twig'),
        ];

        yield 'controller_without_template' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                'FooNoTemplate',
            ])
            ->setArgumentsString('--no-template')
            ->addExtraDependencies('twig')
            ->assert(function (string $output, string $directory) {
                // make sure the template was not configured
                $this->assertContainsCount('created: ', $output, 1);
                $this->assertStringContainsString('created: src/Controller/FooNoTemplateController.php', $output);
                $this->assertStringNotContainsString('created: templates/foo_no_template/index.html.twig', $output);
            }),
        ];

        yield 'controller_sub_namespace' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                'Admin\\FooBar',
            ])
            ->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/src/Controller/Admin/FooBarController.php');

                $this->assertStringContainsString('created: src/Controller/Admin/FooBarController.php', $output);
            }),
        ];

        yield 'controller_sub_namespace_template' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                'Admin\\FooBar',
            ])
            ->addExtraDependencies('twig')
            ->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/templates/admin/foo_bar/index.html.twig');
            }),
        ];

        yield 'controller_full_custom_namespace' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                '\App\Foo\Bar\CoolController',
            ])
            ->addExtraDependencies('twig')
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('created: src/Foo/Bar/CoolController.php', $output);
                $this->assertStringContainsString('created: templates/foo/bar/cool/index.html.twig', $output);
            }),
        ];
    }
}
