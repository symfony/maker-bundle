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

class MakeCrudTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'crud_basic' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCrud::class),
            [
                // entity class name
                'SweetFood',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCrud')
            // need for crud web tests
            ->configureDatabase()
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);
            })
            // workaround for segfault in PHP 7.1 CI :/
            ->setRequiredPhpVersion(70200),
        ];

        yield 'crud_basic_tests' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCrud::class),
            [
                // entity class name
                'SweetFood',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCrud')
            ->addExtraDependencies('symfony/css-selector')
            ->addExtraDependencies('symfony/browser-kit')
            // need for crud web tests
            ->configureDatabase()
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);
                $this->assertStringContainsString('created: tests/Controller/SweetFoodControllerTest.php', $output);
            })
            // workaround for segfault in PHP 7.1 CI :/
            ->setRequiredPhpVersion(70200),
        ];

        yield 'crud_basic_in_custom_root_namespace' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCrud::class),
            [
                // entity class name
                'SweetFood',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCrudInCustomRootNamespace')
            ->changeRootNamespace('Custom')
            // need for crud web tests
            ->configureDatabase()
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);
            })
            // workaround for segfault in PHP 7.1 CI :/
            ->setRequiredPhpVersion(70200),
        ];

        yield 'crud_repository' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCrud::class),
            [
                // entity class name
                'SweetFood',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCrudRepository')
            // need for crud web tests
            ->configureDatabase()
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);
            })
            // workaround for segfault in PHP 7.1 CI :/
            ->setRequiredPhpVersion(70200),
        ];

        yield 'crud_with_no_base' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCrud::class),
            [
                // entity class name
                'SweetFood',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCrud')
            // need for crud web tests
            ->addExtraDependencies('symfony/css-selector')
            ->configureDatabase()
            ->deleteFile('templates/base.html.twig')
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);
            })
            // workaround for segfault in PHP 7.1 CI :/
            ->setRequiredPhpVersion(70200),
        ];
    }
}
