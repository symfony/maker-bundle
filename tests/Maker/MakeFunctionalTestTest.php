<?php

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeFunctionalTest;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

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
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFunctional'),
        ];

        yield 'functional_with_panther' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeFunctionalTest::class),
            [
                // functional test class name
                'FooBar',
            ])
            ->addExtraDependencies('panther')
            ->setRequiredPhpVersion(70100)
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFunctional'),
        ];
    }
}
