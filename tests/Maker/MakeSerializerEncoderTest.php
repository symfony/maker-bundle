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

use Symfony\Bundle\MakerBundle\Maker\MakeSerializerEncoder;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeSerializerEncoderTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'serializer_encoder' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeSerializerEncoder::class),
            [
                // encoder class name
                'FooBarEncoder',
                // encoder format
                'foobar',
            ]),
        ];
    }
}
