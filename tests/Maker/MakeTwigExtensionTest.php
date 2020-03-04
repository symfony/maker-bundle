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
