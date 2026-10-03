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

    public static function getTestDetails(): \Generator
    {
        yield 'it_makes_serializer_encoder' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                if (70000 >= $runner->getSymfonyVersion()) {
                    self::markTestSkipped('Legacy Symfony 6.4 Test');
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
                    'public function decode(string $data, string $format, array $context = []): mixed',
                    file_get_contents($runner->getPath('src/Serializer/FooBarEncoder.php'))
                );
            }),
        ];

        yield 'it_rejects_invalid_format_non_interactively' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], '--no-interaction FooBarEncoder "bad\'name"', allowedToFail: true);

                self::assertStringContainsString('The format "bad\'name" cannot contain quotes or backslashes.', $output);
                self::assertFileDoesNotExist($runner->getPath('src/Serializer/FooBarEncoder.php'));
            }),
        ];

        /* @legacy - Remove when MakerBundle no longer supports Symfony 6.4 */
        yield 'it_makes_serializer_encoder_legacy' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                if (70000 < $runner->getSymfonyVersion()) {
                    self::markTestSkipped('Legacy Symfony 6.4 Test');
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
                    'public function decode(string $data, string $format, array $context = []): mixed',
                    file_get_contents($runner->getPath('src/Serializer/FooBarEncoder.php'))
                );
            }),
        ];
    }
}
