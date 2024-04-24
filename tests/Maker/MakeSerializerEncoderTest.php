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

    public function getTestDetails(): \Generator
    {
        yield 'it_makes_serializer_encoder' => [$this->createMakerTest()
            // serializer-pack 1.1 requires symfony/property-info >= 5.4
            // adding symfony/serializer-pack:* as an extra depends allows
            // us to use serializer-pack < 1.1 which does not conflict with
            // property-info < 5.4. E.g. Symfony 5.3 tests. See PR 1063
            ->addExtraDependencies('symfony/serializer-pack:*')
            ->run(function (MakerTestRunner $runner) {
                if (70000 >= $runner->getSymfonyVersion()) {
                    $this->markTestSkipped('Legacy Symfony 6.4 Test');
                }
                $runner->runMaker(
                    [
                        // encoder class name
                        'FooBarEncoder',
                        // encoder format
                        'foobar',
                    ]
                );

                self::assertStringContainsString(
                    needle: 'public function decode(string $data, string $format, array $context = []): mixed',
                    haystack: file_get_contents($runner->getPath('src/Serializer/FooBarEncoder.php'))
                );
            }),
        ];

        /* @legacy - Remove when MakerBundle no longer supports Symfony 6.4 */
        yield 'it_makes_serializer_encoder_legacy' => [$this->createMakerTest()
            // serializer-pack 1.1 requires symfony/property-info >= 5.4
            // adding symfony/serializer-pack:* as an extra depends allows
            // us to use serializer-pack < 1.1 which does not conflict with
            // property-info < 5.4. E.g. Symfony 5.3 tests. See PR 1063
            ->addExtraDependencies('symfony/serializer-pack:*')
            ->run(function (MakerTestRunner $runner) {
                if (70000 < $runner->getSymfonyVersion()) {
                    $this->markTestSkipped('Legacy Symfony 6.4 Test');
                }
                $runner->runMaker(
                    [
                        // encoder class name
                        'FooBarEncoder',
                        // encoder format
                        'foobar',
                    ]
                );

                self::assertStringNotContainsString(
                    needle: 'public function decode(string $data, string $format, array $context = []): mixed',
                    haystack: file_get_contents($runner->getPath('src/Serializer/FooBarEncoder.php'))
                );
            }),
        ];
    }
}
