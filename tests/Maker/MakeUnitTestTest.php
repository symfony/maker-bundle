<?php

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeUnitTest;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeUnitTestTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'unit_test' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeUnitTest::class),
            [
                // class name
                'FooBar',
            ]),
        ];
    }
}
