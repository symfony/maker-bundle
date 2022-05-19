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

    public function getTestDetails(): \Generator
    {
        yield 'it_generates_basic_crud' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-crud/SweetFood.php',
                    'src/Entity/SweetFood.php'
                );

                $output = $runner->runMaker([
                    'SweetFood', // entity class name
                    '',          // default controller,
                    'n',         // Generate Tests
                ]);

                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);

                $this->runCrudTest($runner, 'it_generates_basic_crud.php');
            }),
        ];

        yield 'it_generates_crud_with_custom_controller' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-crud/SweetFood.php',
                    'src/Entity/SweetFood.php'
                );

                $output = $runner->runMaker([
                    'SweetFood',                // entity class name
                    'SweetFoodAdminController', // default controller,
                    'y',                        // Generate Tests
                ]);

                $this->assertStringContainsString('created: src/Controller/SweetFoodAdminController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);

                $this->runCrudTest($runner, 'it_generates_crud_with_custom_controller.php');
            }),
        ];

        yield 'it_generates_crud_with_tests' => [$this->createMakerTest()
            ->addExtraDependencies('symfony/test-pack')
            ->run(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-crud/SweetFood.php',
                    'src/Entity/SweetFood.php'
                );

                $output = $runner->runMaker([
                    'SweetFood', // Entity Class Name
                    '',          // Default Controller,
                    'y',         // Generate Tests
                ]);

                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);
                $this->assertStringContainsString('created: tests/Controller/SweetFoodControllerTest.php', $output);

                $this->runCrudTest($runner, 'it_generates_basic_crud.php');
            }),
        ];

        yield 'it_generates_crud_custom_repository_with_test' => [$this->createMakerTest()
            ->addExtraDependencies('symfony/test-pack')
            ->run(function (MakerTestRunner $runner) {
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

                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);
                $this->assertStringContainsString('created: tests/Controller/SweetFoodControllerTest.php', $output);

                $this->runCrudTest($runner, 'it_generates_basic_crud.php');
            }),
        ];

        yield 'it_generates_crud_with_custom_root_namespace' => [$this->createMakerTest()
            ->changeRootNamespace('Custom')
            ->run(function (MakerTestRunner $runner) {
                $runner->writeFile(
                    'config/packages/dev/maker.yaml',
                    Yaml::dump(['maker' => ['root_namespace' => 'Custom']])
                );

                $runner->copy(
                    'make-crud/SweetFood-custom-namespace.php',
                    'src/Entity/SweetFood.php'
                );

                $output = $runner->runMaker([
                    'SweetFood', // entity class name
                    '',          // default controller,
                    'n',         // Generate Tests
                ]);

                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);

                $this->runCrudTest($runner, 'it_generates_crud_with_custom_root_namespace.php');
            }),
        ];

        yield 'it_generates_crud_using_custom_repository' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
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

                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);

                $this->runCrudTest($runner, 'it_generates_basic_crud.php');

                self::assertFileEquals(
                    sprintf('%s/fixtures/%s', \dirname(__DIR__), 'make-crud/expected/WithCustomRepository.php'),
                    $runner->getPath('src/Controller/SweetFoodController.php')
                );
            }),
        ];

        yield 'it_generates_crud_with_no_base_template' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
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

                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);

                $this->runCrudTest($runner, 'it_generates_basic_crud.php');
            }),
        ];
    }

    private function runCrudTest(MakerTestRunner $runner, string $filename): void
    {
        $runner->copy(
            'make-crud/tests/'.$filename,
            'tests/GeneratedCrudControllerTest.php'
        );

        $runner->configureDatabase();
        $runner->runTests();
    }
}
