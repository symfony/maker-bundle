<?php

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeTwigExtension;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeTwigExtensionTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'twig_extension' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeTwigExtension::class),
            [
                // extension class name
                'FooBar',
            ]),
        ];
    }
}
