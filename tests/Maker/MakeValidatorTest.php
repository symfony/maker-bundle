<?php

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeValidator;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeValidatorTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'validator' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeValidator::class),
            [
                // validator name
                'FooBar',
            ]),
        ];
    }
}
