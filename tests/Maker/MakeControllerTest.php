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

    public static function getTestDetails(): \Generator
    {
        yield 'it_generates_a_controller' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'FooBar',
                ]);

                self::assertSubstrCount('created: ', $output, 1);
                self::runControllerTest($runner, 'it_generates_a_controller.php');

                // Ensure the generated controller matches what we expect
                self::assertSame(
                    file_get_contents(\dirname(__DIR__).'/fixtures/make-controller/expected/FinalController.php'),
                    file_get_contents($runner->getPath('src/Controller/FooBarController.php'))
                );
            }),
        ];

        yield 'it_generates_a_controller-with-tests' => [self::buildMakerTest()
            ->addExtraDependencies('symfony/test-pack')
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    'FooBar', // controller class name
                    'y', // create tests
                ]);

                self::assertStringContainsString('src/Controller/FooBarController.php', $output);
                self::assertStringContainsString('tests/Controller/FooBarControllerTest.php', $output);

                self::assertFileExists($runner->getPath('src/Controller/FooBarController.php'));
                self::assertFileExists($runner->getPath('tests/Controller/FooBarControllerTest.php'));

                self::runControllerTest($runner, 'it_generates_a_controller.php');
            }),
        ];

        yield 'it_generates_a_controller_with_tests_non_interactively' => [self::buildMakerTest()
            ->addExtraDependencies('symfony/test-pack')
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], 'FooBar --with-tests --no-interaction');

                self::assertStringContainsString('src/Controller/FooBarController.php', $output);
                self::assertStringContainsString('tests/Controller/FooBarControllerTest.php', $output);
                self::assertFileExists($runner->getPath('tests/Controller/FooBarControllerTest.php'));
            }),
        ];

        yield 'it_generates_a_controller__no_input' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], 'FooBar');

                self::assertSubstrCount('created: ', $output, 1);

                self::assertFileExists($runner->getPath('src/Controller/FooBarController.php'));

                self::runControllerTest($runner, 'it_generates_a_controller.php');
            }),
        ];

        yield 'it_generates_a_controller_with_twig' => [self::buildMakerTest()
            ->addExtraDependencies('twig')
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'FooTwig',
                ]);

                $controllerPath = $runner->getPath('templates/foo_twig/index.html.twig');
                self::assertFileExists($controllerPath);

                self::runControllerTest($runner, 'it_generates_a_controller_with_twig.php');

                // Ensure the generated controller matches what we expect
                self::assertSame(
                    file_get_contents(\dirname(__DIR__).'/fixtures/make-controller/expected/FinalControllerWithTemplate.php'),
                    file_get_contents($runner->getPath('src/Controller/FooTwigController.php'))
                );
            }),
        ];

        yield 'it_generates_a_controller_with_twig__no_input' => [self::buildMakerTest()
            ->addExtraDependencies('twig')
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([], 'FooTwig');

                self::assertFileExists($runner->getPath('src/Controller/FooTwigController.php'));
                self::assertFileExists($runner->getPath('templates/foo_twig/index.html.twig'));

                self::runControllerTest($runner, 'it_generates_a_controller_with_twig.php');
            }),
        ];

        yield 'it_generates_a_controller_with_twig_no_base_template' => [self::buildMakerTest()
            ->addExtraDependencies('twig')
            ->run(static function (MakerTestRunner $runner) {
                $runner->deleteFile('templates/base.html.twig');

                $runner->runMaker([
                    // controller class name
                    'FooTwig',
                ]);

                $controllerPath = $runner->getPath('templates/foo_twig/index.html.twig');
                self::assertFileExists($controllerPath);

                self::runControllerTest($runner, 'it_generates_a_controller_with_twig.php');
            }),
        ];

        yield 'it_generates_a_controller_with_without_template' => [self::buildMakerTest()
            ->addExtraDependencies('twig')
            ->run(static function (MakerTestRunner $runner) {
                $runner->deleteFile('templates/base.html.twig');

                $output = $runner->runMaker([
                    // controller class name
                    'FooNoTemplate',
                ], '--no-template');

                // make sure the template was not configured
                self::assertSubstrCount('created: ', $output, 1);
                self::assertStringContainsString('src/Controller/FooNoTemplateController.php', $output);
                self::assertStringNotContainsString('templates/foo_no_template/index.html.twig', $output);
            }),
        ];

        yield 'it_generates_a_controller_in_sub_namespace' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'Admin\\FooBar',
                ]);

                self::assertFileExists($runner->getPath('src/Controller/Admin/FooBarController.php'));
                self::assertStringContainsString('src/Controller/Admin/FooBarController.php', $output);
            }),
        ];

        yield 'it_generates_a_controller_in_sub_namespace__no_input' => [self::buildMakerTest()
            ->skipTest(
                message: 'Test Skipped - MAKER_TEST_WINDOWS is true.',
                skipped: getenv('MAKER_TEST_WINDOWS')
            )
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], 'Admin\\\\FooBar');

                self::assertFileExists($runner->getPath('src/Controller/Admin/FooBarController.php'));
                self::assertStringContainsString('src/Controller/Admin/FooBarController.php', $output);
            }),
        ];

        yield 'it_generates_a_controller_in_sub_namespace_with_template' => [self::buildMakerTest()
            ->addExtraDependencies('twig')
           ->run(static function (MakerTestRunner $runner) {
               $output = $runner->runMaker([
                   // controller class name
                   'Admin\\FooBar',
               ]);

               $controllerPath = $runner->getPath('templates/admin/foo_bar/index.html.twig');
               self::assertFileExists($controllerPath);

               self::assertFileExists($runner->getPath('templates/admin/foo_bar/index.html.twig'));
           }),
        ];

        yield 'it_generates_a_controller_with_full_custom_namespace' => [self::buildMakerTest()
             ->addExtraDependencies('twig')
             ->run(static function (MakerTestRunner $runner) {
                 $output = $runner->runMaker([
                     // controller class name
                     '\App\Foo\Bar\CoolController',
                 ]);

                 $controllerPath = $runner->getPath('templates/foo/bar/cool/index.html.twig');
                 self::assertFileExists($controllerPath);

                 self::assertStringContainsString('src/Foo/Bar/CoolController.php', $output);
                 self::assertStringContainsString('templates/foo/bar/cool/index.html.twig', $output);
             }),
        ];

        yield 'it_generates_a_controller_with_full_custom_namespace__no_input' => [self::buildMakerTest()
            ->skipTest(
                message: 'Test Skipped - MAKER_TEST_WINDOWS is true.',
                skipped: getenv('MAKER_TEST_WINDOWS')
            )
            ->addExtraDependencies('twig')
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], '\\\\App\\\\Foo\\\\Bar\\\\CoolController');

                self::assertFileExists($runner->getPath('templates/foo/bar/cool/index.html.twig'));

                self::assertStringContainsString('src/Foo/Bar/CoolController.php', $output);
                self::assertStringContainsString('templates/foo/bar/cool/index.html.twig', $output);
            }),
        ];

        yield 'it_generates_a_controller_with_invoke' => [self::buildMakerTest()
            ->addExtraDependencies('twig')
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'FooInvokable',
                ], '--invokable');

                $controllerPath = $runner->getPath('templates/foo_invokable.html.twig');
                self::assertFileExists($controllerPath);

                self::assertStringContainsString('src/Controller/FooInvokableController.php', $output);
                self::assertStringContainsString('templates/foo_invokable.html.twig', $output);
                self::runControllerTest($runner, 'it_generates_an_invokable_controller.php');
            }),
        ];

        yield 'it_generates_a_controller_with_invoke_in_sub_namespace' => [self::buildMakerTest()
            ->addExtraDependencies('twig')
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'Admin\\FooInvokable',
                ], '--invokable');

                $controllerPath = $runner->getPath('templates/admin/foo_invokable.html.twig');
                self::assertFileExists($controllerPath);

                self::assertStringContainsString('src/Controller/Admin/FooInvokableController.php', $output);
                self::assertStringContainsString('templates/admin/foo_invokable.html.twig', $output);
            }),
        ];

        yield 'it_rejects_invalid_controller_class_name_non_interactively' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], '"Foo\'Bar" --no-interaction', allowedToFail: true);

                self::assertStringContainsString('The controller class "Foo\'Bar" is not a valid class name.', $output);

                $controllerFiles = glob($runner->getPath('src/Controller/*.php')) ?: [];
                self::assertSame([], $controllerFiles);
            }),
        ];
    }

    private static function assertSubstrCount(string $needle, string $haystack, int $count): void
    {
        self::assertEquals(1, substr_count($haystack, $needle), \sprintf('Found more than %d occurrences of "%s" in "%s"', $count, $needle, $haystack));
    }

    private static function runControllerTest(MakerTestRunner $runner, string $filename): void
    {
        $runner->copy(
            'make-controller/tests/'.$filename,
            'tests/GeneratedControllerTest.php'
        );

        $runner->runTests();
    }
}
