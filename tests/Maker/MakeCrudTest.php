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
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;
use Symfony\Component\Yaml\Yaml;

class MakeCrudTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeCrud::class;
    }

    private function createMakeCrudTest(): MakerTestDetails
    {
        return $this->createMakerTest()
            // workaround for segfault in PHP 7.1 CI :/
            ->setRequiredPhpVersion(70200);
    }

    public function getTestDetails()
    {
        yield 'it_generates_basic_crud' => [$this->createMakeCrudTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-crud/SweetFood.php',
                    'src/Entity/SweetFood.php'
                );

                $output = $runner->runMaker([
                    // entity class name
                    'SweetFood',
                    '', // default controller
                ]);

                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);

                $this->runCrudTest($runner, 'it_generates_basic_crud.php');
            }),
        ];

        yield 'it_generates_crud_with_custom_controller' => [$this->createMakeCrudTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-crud/SweetFood.php',
                    'src/Entity/SweetFood.php'
                );

                $output = $runner->runMaker([
                    // entity class name
                    'SweetFood',
                    'SweetFoodAdminController', // default controller
                ]);

                $this->assertStringContainsString('created: src/Controller/SweetFoodAdminController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);

                $this->runCrudTest($runner, 'it_generates_crud_with_custom_controller.php');
            }),
        ];

        yield 'it_generates_crud_with_custom_root_namespace' => [$this->createMakeCrudTest()
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
                    // entity class name
                    'SweetFood',
                    '', // default controller
                ]);

                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);

                $this->runCrudTest($runner, 'it_generates_crud_with_custom_root_namespace.php');
            }),
        ];

        yield 'it_generates_crud_using_custom_repository' => [$this->createMakeCrudTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-crud/SweetFood.php',
                    'src/Entity/SweetFood.php'
                );
                $runner->copy(
                    'make-crud/SweetFoodRepository.php',
                    'src/Repository/SweetFoodRepository.php'
                );

                $output = $runner->runMaker([
                    // entity class name
                    'SweetFood',
                    '', // default controller
                ]);

                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);

                $this->runCrudTest($runner, 'it_generates_basic_crud.php');
            }),
        ];

        yield 'it_generates_crud_with_no_base_template' => [$this->createMakeCrudTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-crud/SweetFood.php',
                    'src/Entity/SweetFood.php'
                );

                $runner->deleteFile('templates/base.html.twig');

                $output = $runner->runMaker([
                    // entity class name
                    'SweetFood',
                    '', // default controller
                ]);

                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);

                $this->runCrudTest($runner, 'it_generates_basic_crud.php');
            }),
        ];
    }

    private function runCrudTest(MakerTestRunner $runner, string $filename)
    {
        $runner->copy(
            'make-crud/tests/'.$filename,
            'tests/GeneratedCrudControllerTest.php'
        );

        $runner->configureDatabase();
        $runner->runTests();
    }
}
