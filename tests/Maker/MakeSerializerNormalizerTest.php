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

use Symfony\Bundle\MakerBundle\Maker\MakeSerializerNormalizer;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeSerializerNormalizerTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'serializer_normalizer' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeSerializerNormalizer::class),
            [
                // normalizer class name
                'FooBarNormalizer',
            ]),
        ];
    }
}
