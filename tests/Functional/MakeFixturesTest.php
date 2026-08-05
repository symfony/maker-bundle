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

use Symfony\Bundle\MakerBundle\Maker\MakeFixtures;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeFixtures::class, profile: Profiles::MEGA)]
final class MakeFixturesTest extends MakerTestCase
{
    #[LegacyCase('MakeFixturesTest::it_generates_fixtures')]
    public function testItGeneratesFixtures()
    {
        $this->app->prepareDatabase();

        $this->app->runMaker()
            ->answer('The class name of the fixtures to create', 'FooFixtures')
            ->run()
            ->assertOutputContains('src/DataFixtures/FooFixtures.php')
            ->assertCreated('src/DataFixtures/FooFixtures.php');

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            /** @var \App\DataFixtures\FooFixtures $fixtures */
            $fixtures = static::getContainer()->get(\App\DataFixtures\FooFixtures::class);
            $fixtures->load(static::getContainer()->get('doctrine')->getManager());

            self::assertInstanceOf(\Doctrine\Bundle\FixturesBundle\Fixture::class, $fixtures);
            PHP,
            covers: ['src/DataFixtures/FooFixtures.php'],
        );
    }
}
