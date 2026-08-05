<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Functional;

use Symfony\Bundle\MakerBundle\Maker\MakeSerializerEncoder;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\RequiresSymfony;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeSerializerEncoder::class, profile: Profiles::MEGA)]
final class MakeSerializerEncoderTest extends MakerTestCase
{
    #[LegacyCase('MakeSerializerEncoderTest::it_makes_serializer_encoder')]
    #[RequiresSymfony('>=7.0')]
    public function testItMakesSerializerEncoder()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your encoder', 'FooBarEncoder')
            ->answer('Pick your format name', 'foobar')
            ->run()
            ->assertCreated('src/Serializer/FooBarEncoder.php');

        $this->app->assertFileContains(
            'src/Serializer/FooBarEncoder.php',
            'public function decode(string $data, string $format, array $context = []): mixed',
        );

        $this->assertEncoderExecutes();
    }

    /* @legacy - Remove when MakerBundle no longer supports Symfony 6.4 */
    #[LegacyCase('MakeSerializerEncoderTest::it_makes_serializer_encoder_legacy')]
    #[RequiresSymfony('<7.0')]
    public function testItMakesSerializerEncoderLegacy()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your encoder', 'FooBarEncoder')
            ->answer('Pick your format name', 'foobar')
            ->run()
            ->assertCreated('src/Serializer/FooBarEncoder.php');

        $this->app->assertFileNotContains(
            'src/Serializer/FooBarEncoder.php',
            'public function decode(string $data, string $format, array $context = []): mixed',
        );

        $this->assertEncoderExecutes();
    }

    private function assertEncoderExecutes(): void
    {
        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $encoder = new \App\Serializer\FooBarEncoder();

            self::assertIsBool($encoder->supportsEncoding('foobar'));
            self::assertIsBool($encoder->supportsDecoding('foobar'));
            PHP,
            covers: ['src/Serializer/FooBarEncoder.php'],
        );
    }
}
