<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeController;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

class MakeControllerTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeController::class;
    }

    // @legacy Remove when Symfony 5.4 is no longer supported
    private function getControllerTest(): MakerTestDetails
    {
        return $this
            ->createMakerTest()
            ->preRun(function (MakerTestRunner $runner) {
                if ($runner->getSymfonyVersion() < 60000) {
                    // Because MakeController::configureDependencies() is executed in the main thread,
                    // we need to manually add in `doctrine/annotations` for Symfony 5.4 tests.
                    $runner->runProcess('composer require doctrine/annotations');
                }
            });
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_generates_a_controller' => [$this->getControllerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'FooBar',
                ]);

                $this->assertContainsCount('created: ', $output, 1);

                $this->runControllerTest($runner, 'it_generates_a_controller.php');
            }),
        ];

        yield 'it_generates_a_controller_with_twig' => [$this->getControllerTest()
            ->addExtraDependencies('twig')
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'FooTwig',
                ]);

                $this->runControllerTest($runner, 'it_generates_a_controller_with_twig.php');
            }),
        ];

        yield 'it_generates_a_controller_with_twig_no_base_template' => [$this->getControllerTest()
            ->addExtraDependencies('twig')
            ->run(function (MakerTestRunner $runner) {
                $runner->deleteFile('templates/base.html.twig');

                $runner->runMaker([
                    // controller class name
                    'FooTwig',
                ]);

                $this->runControllerTest($runner, 'it_generates_a_controller_with_twig.php');
            }),
        ];

        yield 'it_generates_a_controller_with_without_template' => [$this->getControllerTest()
            ->addExtraDependencies('twig')
            ->run(function (MakerTestRunner $runner) {
                $runner->deleteFile('templates/base.html.twig');

                $output = $runner->runMaker([
                    // controller class name
                    'FooNoTemplate',
                ], '--no-template');

                // make sure the template was not configured
                $this->assertContainsCount('created: ', $output, 1);
                $this->assertStringContainsString('created: src/Controller/FooNoTemplateController.php', $output);
                $this->assertStringNotContainsString('created: templates/foo_no_template/index.html.twig', $output);
            }),
        ];

        yield 'it_generates_a_controller_in_sub_namespace' => [$this->getControllerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'Admin\\FooBar',
                ]);

                $this->assertFileExists($runner->getPath('src/Controller/Admin/FooBarController.php'));
                $this->assertStringContainsString('created: src/Controller/Admin/FooBarController.php', $output);
            }),
        ];

        yield 'it_generates_a_controller_in_sub_namespace_with_template' => [$this->getControllerTest()
            ->addExtraDependencies('twig')
           ->run(function (MakerTestRunner $runner) {
               $output = $runner->runMaker([
                   // controller class name
                   'Admin\\FooBar',
               ]);

               $this->assertFileExists($runner->getPath('templates/admin/foo_bar/index.html.twig'));
           }),
       ];

        yield 'it_generates_a_controller_with_full_custom_namespace' => [$this->getControllerTest()
            ->addExtraDependencies('twig')
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    '\App\Foo\Bar\CoolController',
                ]);

                $this->assertStringContainsString('created: src/Foo/Bar/CoolController.php', $output);
                $this->assertStringContainsString('created: templates/foo/bar/cool/index.html.twig', $output);
            }),
        ];
    }

    private function runControllerTest(MakerTestRunner $runner, string $filename): void
    {
        $runner->copy(
            'make-controller/tests/'.$filename,
            'tests/GeneratedControllerTest.php'
        );

        $runner->runTests();
    }
}
