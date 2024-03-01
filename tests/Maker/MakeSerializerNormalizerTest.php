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

    public function getTestDetails(): \Generator
    {
        yield 'it_makes_serializer_normalizer' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker(
                    ['FooBarNormalizer']
                );

                $this->assertStringContainsString('Success', $output);

                self::assertFileEquals(
                    \dirname(__DIR__).'/fixtures/make-serializer-normalizer/FooBarNormalizer.php',
                    $runner->getPath('src/Serializer/Normalizer/FooBarNormalizer.php')
                );
            }),
        ];

        yield 'it_makes_serializer_normalizer_with_existing_entity' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->copy('make-serializer-normalizer/EntityFixture.php', 'src/Entity/EntityFixture.php');

                $output = $runner->runMaker(
                    ['EntityFixture']
                );

                $this->assertStringContainsString('Success', $output);

                self::assertFileEquals(
                    \dirname(__DIR__).'/fixtures/make-serializer-normalizer/EntityFixtureNormalizer.php',
                    $runner->getPath('src/Serializer/Normalizer/EntityFixtureNormalizer.php')
                );
            }),
        ];
    }
}
