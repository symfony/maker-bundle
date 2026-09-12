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

use Symfony\Bundle\MakerBundle\Maker\MakeCrud;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;
use Symfony\Component\Yaml\Yaml;

class MakeCrudTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeCrud::class;
    }

    public static function getTestDetails(): \Generator
    {
        yield 'it_generates_basic_crud' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-crud/SweetFood.php',
                    'src/Entity/SweetFood.php'
                );

                $output = $runner->runMaker([
                    'SweetFood', // entity class name
                    '',          // default controller,
                    'n',         // Generate Tests
                ]);

                self::assertStringContainsString('src/Controller/SweetFoodController.php', $output);
                self::assertStringContainsString('src/Form/SweetFoodType.php', $output);

                self::runCrudTest($runner, 'it_generates_basic_crud.php');
            }),
        ];

        yield 'it_generates_crud_with_custom_controller' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-crud/SweetFood.php',
                    'src/Entity/SweetFood.php'
                );

                $output = $runner->runMaker([
                    'SweetFood',                // entity class name
                    'SweetFoodAdminController', // default controller,
                    'y',                        // Generate Tests
                ]);

                self::assertStringContainsString('src/Controller/SweetFoodAdminController.php', $output);
                self::assertStringContainsString('src/Form/SweetFoodType.php', $output);

                self::runCrudTest($runner, 'it_generates_crud_with_custom_controller.php');
            }),
        ];

        yield 'it_generates_crud_non_interactively_with_a_controller_class' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-crud/SweetFood.php',
                    'src/Entity/SweetFood.php'
                );

                $output = $runner->runMaker(
                    [],
                    '--no-interaction SweetFood --controller-class=SweetFoodAdminController'
                );

                self::assertStringContainsString('src/Controller/SweetFoodAdminController.php', $output);
                self::assertStringContainsString(
                    'class SweetFoodAdminController',
                    file_get_contents($runner->getPath('src/Controller/SweetFoodAdminController.php'))
                );

                self::runCrudTest($runner, 'it_generates_crud_with_custom_controller.php');
            }),
        ];

        yield 'it_generates_crud_non_interactively_without_a_controller_class' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-crud/SweetFood.php',
                    'src/Entity/SweetFood.php'
                );

                // the name the interactive mode offers as its default, without being asked for it
                $output = $runner->runMaker([], '--no-interaction SweetFood');

                self::assertStringContainsString('src/Controller/SweetFoodController.php', $output);

                self::runCrudTest($runner, 'it_generates_basic_crud.php');
            }),
        ];

        yield 'it_generates_crud_with_tests' => [self::buildMakerTest()
            ->addExtraDependencies('symfony/test-pack')
            ->run(static function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-crud/SweetFood.php',
                    'src/Entity/SweetFood.php'
                );

                $output = $runner->runMaker([
                    'SweetFood', // Entity Class Name
                    '',          // Default Controller,
                    'y',         // Generate Tests
                ]);

                self::assertStringContainsString('src/Controller/SweetFoodController.php', $output);
                self::assertStringContainsString('src/Form/SweetFoodType.php', $output);
                self::assertStringContainsString('tests/Controller/SweetFoodControllerTest.php', $output);

                self::runCrudTest($runner, 'it_generates_basic_crud.php');
            }),
        ];

        yield 'it_generates_crud_with_tests_when_phpunit_is_not_installed' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-crud/SweetFood.php',
                    'src/Entity/SweetFood.php'
                );

                // Simulate a project without PHPUnit installed: generating tests must not crash.
                $runner->writeFile(
                    'remove_phpunit.php',
                    '<?php $config = json_decode(file_get_contents("composer.json"), true); unset($config["require-dev"]["phpunit/phpunit"]); file_put_contents("composer.json", json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));',
                );
                $runner->runProcess('php remove_phpunit.php && composer update --no-interaction && rm remove_phpunit.php');

                $output = $runner->runMaker([
                    'SweetFood', // Entity Class Name
                    '',          // Default Controller,
                    'y',         // Generate Tests
                ]);

                self::assertStringContainsString('src/Controller/SweetFoodController.php', $output);
                self::assertStringContainsString('src/Form/SweetFoodType.php', $output);
                self::assertStringContainsString('tests/Controller/SweetFoodControllerTest.php', $output);
                self::assertStringContainsString('symfony/test-pack', $output);
            }),
        ];

        yield 'it_generates_correct_class_methods' => [self::buildMakerTest()
            ->addExtraDependencies('symfony/test-pack')
            ->run(static function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-crud/Foo.php',
                    'src/Entity/Foo.php'
                );

                $output = $runner->runMaker([
                    'Foo', // Entity Class Name
                    '',    // Default Controller,
                    'y',   // Generate Tests
                ]);

                self::assertStringContainsString('src/Controller/FooController.php', $output);
                self::assertStringContainsString('src/Form/FooType.php', $output);
                self::assertStringContainsString('tests/Controller/FooControllerTest.php', $output);

                self::runCrudTest($runner, 'it_generates_correct_class_methods.php');
            }),
        ];

        yield 'it_generates_crud_custom_repository_with_test' => [self::buildMakerTest()
            ->addExtraDependencies('symfony/test-pack')
            ->run(static function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-crud/SweetFoodCustomRepository.php',
                    'src/Entity/SweetFood.php'
                );

                $runner->copy(
                    'make-crud/SweetFoodRepository.php',
                    'src/Repository/SweetFoodRepository.php'
                );

                $output = $runner->runMaker([
                    'SweetFood', // Entity Class Name
                    '',          // Default Controller,
                    'y',         // Generate Tests
                ]);

                self::assertStringContainsString('src/Controller/SweetFoodController.php', $output);
                self::assertStringContainsString('src/Form/SweetFoodType.php', $output);
                self::assertStringContainsString('tests/Controller/SweetFoodControllerTest.php', $output);

                self::runCrudTest($runner, 'it_generates_basic_crud.php');
            }),
        ];

        yield 'it_generates_crud_with_custom_root_namespace' => [self::buildMakerTest()
            ->changeRootNamespace('Custom')
            ->run(static function (MakerTestRunner $runner) {
                $runner->writeFile(
                    'config/packages/dev/maker.yaml',
                    Yaml::dump(['maker' => ['root_namespace' => 'Custom']])
                );

                // Symfony 6.2 sets the path and namespace for router resources
                $runner->modifyYamlFile('config/routes.yaml', static function (array $config) {
                    if (!isset($config['controllers']['resource']['namespace'])) {
                        return $config;
                    }

                    $config['controllers']['resource']['namespace'] = 'Custom\Controller';

                    return $config;
                });

                $runner->copy(
                    'make-crud/SweetFood-custom-namespace.php',
                    'src/Entity/SweetFood.php'
                );

                $output = $runner->runMaker([
                    'SweetFood', // entity class name
                    '',          // default controller,
                    'n',         // Generate Tests
                ]);

                self::assertStringContainsString('src/Controller/SweetFoodController.php', $output);
                self::assertStringContainsString('src/Form/SweetFoodType.php', $output);

                self::runCrudTest($runner, 'it_generates_crud_with_custom_root_namespace.php');
            }),
        ];

        yield 'it_generates_crud_using_custom_repository' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-crud/SweetFoodCustomRepository.php',
                    'src/Entity/SweetFood.php'
                );
                $runner->copy(
                    'make-crud/SweetFoodRepository.php',
                    'src/Repository/SweetFoodRepository.php'
                );

                $output = $runner->runMaker([
                    'SweetFood', // entity class name
                    '',          // default controller,
                    'n',         // Generate Tests
                ]);

                self::assertStringContainsString('src/Controller/SweetFoodController.php', $output);
                self::assertStringContainsString('src/Form/SweetFoodType.php', $output);

                self::runCrudTest($runner, 'it_generates_basic_crud.php');
                self::assertFileEquals(
                    \sprintf('%s/fixtures/make-crud/expected/WithCustomRepository.php', \dirname(__DIR__)),
                    $runner->getPath('src/Controller/SweetFoodController.php')
                );
            }),
        ];

        yield 'it_generates_crud_with_no_base_template' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-crud/SweetFood.php',
                    'src/Entity/SweetFood.php'
                );

                $runner->deleteFile('templates/base.html.twig');

                $output = $runner->runMaker([
                    'SweetFood', // entity class name
                    '',          // default controller,
                    'n',         // Generate Tests
                ]);

                self::assertStringContainsString('src/Controller/SweetFoodController.php', $output);
                self::assertStringContainsString('src/Form/SweetFoodType.php', $output);

                self::runCrudTest($runner, 'it_generates_basic_crud.php');
            }),
        ];
    }

    private static function runCrudTest(MakerTestRunner $runner, string $filename): void
    {
        $runner->copy(
            'make-crud/tests/'.$filename,
            'tests/GeneratedCrudControllerTest.php'
        );

        $runner->configureDatabase();
        $runner->runTests();
    }
}
