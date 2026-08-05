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

use Symfony\Bundle\MakerBundle\Maker\MakeVoter;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeVoter::class, profile: Profiles::MEGA)]
final class MakeVoterTest extends MakerTestCase
{
    #[LegacyCase('MakeVoterTest::it_makes_voter')]
    public function testItMakesVoter()
    {
        $this->app->runMaker()
            ->answer('The name of the security voter class', 'FooBar')
            ->run()
            ->assertCreated('src/Security/Voter/FooBarVoter.php');

        $this->app->assertFileMatchesFixture('src/Security/Voter/FooBarVoter.php', 'expected/FooBarVoter.php');

        $this->runVoterAssertions();
    }

    #[LegacyCase('MakeVoterTest::it_makes_voter_not_final')]
    public function testItMakesVoterNotFinal()
    {
        $this->app->writeYaml('config/packages/dev/maker.yaml', ['maker' => ['generate_final_classes' => false]]);

        $this->app->runMaker()
            ->answer('The name of the security voter class', 'FooBar')
            ->run()
            ->assertCreated('src/Security/Voter/FooBarVoter.php');

        $this->app->assertFileMatchesFixture('src/Security/Voter/FooBarVoter.php', 'expected/not_final_FooBarVoter.php');

        $this->runVoterAssertions();
    }

    private function runVoterAssertions(): void
    {
        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $voter = new \App\Security\Voter\FooBarVoter();

            // the generated supports() only accepts \App\Entity\FooBar subjects,
            // so any other subject must lead to ACCESS_ABSTAIN
            $result = $voter->vote(
                new \Symfony\Component\Security\Core\Authentication\Token\NullToken(),
                new \stdClass(),
                [\App\Security\Voter\FooBarVoter::VIEW],
            );

            self::assertSame(\Symfony\Component\Security\Core\Authorization\Voter\VoterInterface::ACCESS_ABSTAIN, $result);
            PHP,
            covers: ['src/Security/Voter/FooBarVoter.php'],
        );
    }
}
