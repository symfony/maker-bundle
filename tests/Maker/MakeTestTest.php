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
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeTestTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'TestCase' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeTest::class),
            [
                // class name
                'FooBar',
                // type
                'TestCase',
            ]),
        ];

        yield 'KernelTestCase' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeTest::class),
            [
                // functional test class name
                'FooBar',
                // type
                'KernelTestCase',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFunctional'),
        ];

        yield 'WebTestCase' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeTest::class),
            [
                // functional test class name
                'FooBar',
                // type
                'WebTestCase',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFunctional'),
        ];

        yield 'PantherTestCase' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeTest::class),
            [
                // functional test class name
                'FooBar',
                // type
                'PantherTestCase',
            ])
            ->addExtraDependencies('panther')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFunctional'),
        ];
    }
}
