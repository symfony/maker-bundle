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
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

class MakeSerializerNormalizerTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeSerializerNormalizer::class;
    }

    public function getTestDetails()
    {
        yield 'it_makes_serializer_normalizer' => [$this->createMakerTest()
            // serializer-pack 1.1 requires symfony/property-info >= 5.4
            // adding symfony/serializer-pack:* as an extra depends allows
            // us to use serializer-pack < 1.1 which does not conflict with
            // property-info < 5.4. E.g. Symfony 5.3 tests. See PR 1063
            ->addExtraDependencies('symfony/serializer-pack:*')
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // normalizer class name
                        'FooBarNormalizer',
                    ]
                );
            }),
        ];
    }
}
