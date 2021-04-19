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
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

/**
 * @group legacy
 */
class MakeFunctionalTestTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'functional_maker' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeFunctionalTest::class),
            [
                // functional test class name
                'FooBar',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFunctional')
            ->skip('See https://github.com/symfony/maker-bundle/pull/807/files#r571308250'),
        ];

        yield 'functional_with_panther' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeFunctionalTest::class),
            [
                // functional test class name
                'FooBar',
            ])
            ->addExtraDependencies('panther')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFunctional'),
        ];
    }
}
