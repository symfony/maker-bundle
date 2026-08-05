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

use Symfony\Bundle\MakerBundle\Maker\MakeSerializerNormalizer;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeSerializerNormalizer::class, profile: Profiles::MEGA)]
final class MakeSerializerNormalizerTest extends MakerTestCase
{
    #[LegacyCase('MakeSerializerNormalizerTest::it_makes_serializer_normalizer')]
    public function testItMakesSerializerNormalizer()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your normalizer', 'FooBarNormalizer')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('src/Serializer/Normalizer/FooBarNormalizer.php');

        $this->app->assertFileMatchesFixture('src/Serializer/Normalizer/FooBarNormalizer.php', 'FooBarNormalizer.php');

        $this->assertNormalizerExecutes('FooBarNormalizer', 'new \stdClass()');
    }

    #[LegacyCase('MakeSerializerNormalizerTest::it_makes_serializer_normalizer_with_existing_entity')]
    public function testItMakesSerializerNormalizerWithExistingEntity()
    {
        $this->app->copyFixture('EntityFixture.php', 'src/Entity/EntityFixture.php');

        $this->app->runMaker()
            ->answer('Choose a class name for your normalizer', 'EntityFixture')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('src/Serializer/Normalizer/EntityFixtureNormalizer.php');

        $this->app->assertFileMatchesFixture('src/Serializer/Normalizer/EntityFixtureNormalizer.php', 'EntityFixtureNormalizer.php');

        $this->assertNormalizerExecutes('EntityFixtureNormalizer', 'new \App\Entity\EntityFixture()');
    }

    private function assertNormalizerExecutes(string $shortClass, string $objectExpression): void
    {
        $this->app->assertGeneratedCodeRuns(<<<PHP
            \$normalizer = new \\App\\Serializer\\Normalizer\\{$shortClass}(
                static::getContainer()->get('serializer.normalizer.object'),
            );

            self::assertIsArray(\$normalizer->normalize({$objectExpression}));
            PHP,
            covers: ["src/Serializer/Normalizer/{$shortClass}.php"],
        );
    }
}
