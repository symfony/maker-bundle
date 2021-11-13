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
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

class MakeSerializerEncoderTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeSerializerEncoder::class;
    }

    public function getTestDetails()
    {
        yield 'it_makes_serializer_encoder' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // encoder class name
                        'FooBarEncoder',
                        // encoder format
                        'foobar',
                    ]
                );
            }),
        ];
    }
}
