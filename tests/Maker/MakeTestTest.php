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

use Symfony\Bundle\MakerBundle\Maker\MakeTest;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

class MakeTestTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeTest::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_makes_TestCase_type' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // type
                        'TestCase',
                        // class name
                        'FooBar',
                    ]
                );

                $runner->runTests();
            }),
        ];

        yield 'it_makes_KernelTestCase_type' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-test/basic_setup',
                    ''
                );

                $runner->runMaker(
                    [
                        // type
                        'KernelTestCase',
                        // functional test class name
                        'FooBar',
                    ]
                );

                $runner->runTests();
            }),
        ];

        yield 'it_makes_WebTestCase_type' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-test/basic_setup',
                    ''
                );

                $runner->runMaker(
                    [
                        // type
                        'WebTestCase',
                        // functional test class name
                        'FooBar',
                    ]
                );

                $runner->runTests();
            }),
        ];

        yield 'it_makes_PantherTestCase_type' => [$this->createMakerTest()
            ->skipOnSymfony7() // legacy remove when panther supports Symfony 7
            /* @legacy Allows Panther >= 1.x to be installed. (PHP <8.0 support) */
            ->addExtraDependencies('panther:*')
            ->run(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-test/basic_setup',
                    ''
                );

                $runner->runMaker(
                    [
                        // type
                        'PantherTestCase',
                        // functional test class name
                        'FooBar',
                    ]
                );

                $runner->runProcess('composer require --dev dbrekelmans/bdi');
                $runner->runProcess('php vendor/dbrekelmans/bdi/bdi detect drivers');

                $runner->runTests();
            }),
        ];
    }
}
