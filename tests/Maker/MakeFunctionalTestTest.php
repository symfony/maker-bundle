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

use Symfony\Bundle\MakerBundle\Maker\MakeFunctionalTest;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

/**
 * @group legacy
 */
class MakeFunctionalTestTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeFunctionalTest::class;
    }

    public function getTestDetails()
    {
        yield 'it_generates_test_with_panther' => [$this->createMakerTest()
            /* @legacy Allows Panther >= 1.x to be installed. (PHP <8.0 support) */
            ->skipOnSymfony7() // legacy remove when panther supports Symfony 7
            ->addExtraDependencies('panther:*')
            ->run(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-functional/MainController.php',
                    'src/Controller/MainController.php'
                );
                $runner->copy(
                    'make-functional/routes.yaml',
                    'config/routes.yaml'
                );

                $runner->runMaker([
                    // functional test class name
                    'FooBar',
                ]);

                $runner->runTests();
            }),
        ];
    }
}
