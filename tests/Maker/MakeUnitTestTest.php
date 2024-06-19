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

use Symfony\Bundle\MakerBundle\Maker\MakeUnitTest;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

/**
 * @group legacy
 */
class MakeUnitTestTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeUnitTest::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_makes_unit_test' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // class name
                        'FooBar',
                    ]
                );

                $runner->runTests();
            }),
        ];
    }
}
