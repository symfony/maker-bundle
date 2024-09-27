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
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

/**
 * Passing namespaces interactively can be done like "App\Controller\MyController"
 * but passing as a command argument, you must add a double set of slashes. e.g.
 * "App\\\\Controller\\\\MyController".
 */
class MakeControllerTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeController::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_generates_a_controller' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'FooBar',
                ]);

                $this->assertContainsCount('created: ', $output, 1);
                $this->runControllerTest($runner, 'it_generates_a_controller.php');

                // Ensure the generated controller matches what we expect
                self::assertSame(
                    expected: file_get_contents(\dirname(__DIR__).'/fixtures/make-controller/expected/FinalController.php'),
                    actual: file_get_contents($runner->getPath('src/Controller/FooBarController.php'))
                );
            }),
        ];

        yield 'it_generates_a_controller-with-tests' => [$this->createMakerTest()
            ->addExtraDependencies('symfony/test-pack')
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    'FooBar', // controller class name
                    'y', // create tests
                ]);

                $this->assertStringContainsString('src/Controller/FooBarController.php', $output);
                $this->assertStringContainsString('tests/Controller/FooBarControllerTest.php', $output);

                $this->assertFileExists($runner->getPath('src/Controller/FooBarController.php'));
                $this->assertFileExists($runner->getPath('tests/Controller/FooBarControllerTest.php'));

                $this->runControllerTest($runner, 'it_generates_a_controller.php');
            }),
        ];

        yield 'it_generates_a_controller__no_input' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], 'FooBar');

                $this->assertContainsCount('created: ', $output, 1);

                $this->assertFileExists($runner->getPath('src/Controller/FooBarController.php'));

                $this->runControllerTest($runner, 'it_generates_a_controller.php');
            }),
        ];

        yield 'it_generates_a_controller_with_twig' => [$this->createMakerTest()
            ->addExtraDependencies('twig')
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'FooTwig',
                ]);

                $controllerPath = $runner->getPath('templates/foo_twig/index.html.twig');
                self::assertFileExists($controllerPath);

                $this->runControllerTest($runner, 'it_generates_a_controller_with_twig.php');

                // Ensure the generated controller matches what we expect
                self::assertSame(
                    expected: file_get_contents(\dirname(__DIR__).'/fixtures/make-controller/expected/FinalControllerWithTemplate.php'),
                    actual: file_get_contents($runner->getPath('src/Controller/FooTwigController.php'))
                );
            }),
        ];

        yield 'it_generates_a_controller_with_twig__no_input' => [$this->createMakerTest()
            ->addExtraDependencies('twig')
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker([], 'FooTwig');

                $this->assertFileExists($runner->getPath('src/Controller/FooTwigController.php'));
                $this->assertFileExists($runner->getPath('templates/foo_twig/index.html.twig'));

                $this->runControllerTest($runner, 'it_generates_a_controller_with_twig.php');
            }),
        ];

        yield 'it_generates_a_controller_with_twig_no_base_template' => [$this->createMakerTest()
            ->addExtraDependencies('twig')
            ->run(function (MakerTestRunner $runner) {
                $runner->deleteFile('templates/base.html.twig');

                $runner->runMaker([
                    // controller class name
                    'FooTwig',
                ]);

                $controllerPath = $runner->getPath('templates/foo_twig/index.html.twig');
                self::assertFileExists($controllerPath);

                $this->runControllerTest($runner, 'it_generates_a_controller_with_twig.php');
            }),
        ];

        yield 'it_generates_a_controller_with_without_template' => [$this->createMakerTest()
            ->addExtraDependencies('twig')
            ->run(function (MakerTestRunner $runner) {
                $runner->deleteFile('templates/base.html.twig');

                $output = $runner->runMaker([
                    // controller class name
                    'FooNoTemplate',
                ], '--no-template');

                // make sure the template was not configured
                $this->assertContainsCount('created: ', $output, 1);
                $this->assertStringContainsString('src/Controller/FooNoTemplateController.php', $output);
                $this->assertStringNotContainsString('templates/foo_no_template/index.html.twig', $output);
            }),
        ];

        yield 'it_generates_a_controller_in_sub_namespace' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'Admin\\FooBar',
                ]);

                $this->assertFileExists($runner->getPath('src/Controller/Admin/FooBarController.php'));
                $this->assertStringContainsString('src/Controller/Admin/FooBarController.php', $output);
            }),
        ];

        yield 'it_generates_a_controller_in_sub_namespace__no_input' => [$this->createMakerTest()
            ->skipTest(
                message: 'Test Skipped - MAKER_TEST_WINDOWS is true.',
                skipped: getenv('MAKER_TEST_WINDOWS')
            )
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], 'Admin\\\\FooBar');

                $this->assertFileExists($runner->getPath('src/Controller/Admin/FooBarController.php'));
                $this->assertStringContainsString('src/Controller/Admin/FooBarController.php', $output);
            }),
        ];

        yield 'it_generates_a_controller_in_sub_namespace_with_template' => [$this->createMakerTest()
            ->addExtraDependencies('twig')
           ->run(function (MakerTestRunner $runner) {
               $output = $runner->runMaker([
                   // controller class name
                   'Admin\\FooBar',
               ]);

               $controllerPath = $runner->getPath('templates/admin/foo_bar/index.html.twig');
               self::assertFileExists($controllerPath);

               $this->assertFileExists($runner->getPath('templates/admin/foo_bar/index.html.twig'));
           }),
        ];

        yield 'it_generates_a_controller_with_full_custom_namespace' => [$this->createMakerTest()
             ->addExtraDependencies('twig')
             ->run(function (MakerTestRunner $runner) {
                 $output = $runner->runMaker([
                     // controller class name
                     '\App\Foo\Bar\CoolController',
                 ]);

                 $controllerPath = $runner->getPath('templates/foo/bar/cool/index.html.twig');
                 self::assertFileExists($controllerPath);

                 $this->assertStringContainsString('src/Foo/Bar/CoolController.php', $output);
                 $this->assertStringContainsString('templates/foo/bar/cool/index.html.twig', $output);
             }),
        ];

        yield 'it_generates_a_controller_with_full_custom_namespace__no_input' => [$this->createMakerTest()
            ->skipTest(
                message: 'Test Skipped - MAKER_TEST_WINDOWS is true.',
                skipped: getenv('MAKER_TEST_WINDOWS')
            )
            ->addExtraDependencies('twig')
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], '\\\\App\\\\Foo\\\\Bar\\\\CoolController');

                self::assertFileExists($runner->getPath('templates/foo/bar/cool/index.html.twig'));

                $this->assertStringContainsString('src/Foo/Bar/CoolController.php', $output);
                $this->assertStringContainsString('templates/foo/bar/cool/index.html.twig', $output);
            }),
        ];

        yield 'it_generates_a_controller_with_invoke' => [$this->createMakerTest()
            ->addExtraDependencies('twig')
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'FooInvokable',
                ], '--invokable');

                $controllerPath = $runner->getPath('templates/foo_invokable.html.twig');
                self::assertFileExists($controllerPath);

                $this->assertStringContainsString('src/Controller/FooInvokableController.php', $output);
                $this->assertStringContainsString('templates/foo_invokable.html.twig', $output);
                $this->runControllerTest($runner, 'it_generates_an_invokable_controller.php');
            }),
        ];

        yield 'it_generates_a_controller_with_invoke_in_sub_namespace' => [$this->createMakerTest()
            ->addExtraDependencies('twig')
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'Admin\\FooInvokable',
                ], '--invokable');

                $controllerPath = $runner->getPath('templates/admin/foo_invokable.html.twig');
                self::assertFileExists($controllerPath);

                $this->assertStringContainsString('src/Controller/Admin/FooInvokableController.php', $output);
                $this->assertStringContainsString('templates/admin/foo_invokable.html.twig', $output);
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
