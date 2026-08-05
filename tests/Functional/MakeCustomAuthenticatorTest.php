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

use Symfony\Bundle\MakerBundle\Maker\Security\MakeCustomAuthenticator;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeCustomAuthenticator::class, profile: Profiles::MEGA)]
final class MakeCustomAuthenticatorTest extends MakerTestCase
{
    #[LegacyCase('Security\MakeCustomAuthenticatorTest::generates_custom_authenticator')]
    public function testGeneratesCustomAuthenticator()
    {
        $this->app->runMaker()
            ->answer('What is the class name of the authenticator', 'FixtureAuthenticator')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('src/Security/FixtureAuthenticator.php')
            ->assertUpdated('config/packages/security.yaml');

        $this->app->assertFileMatchesFixture('src/Security/FixtureAuthenticator.php', 'expected/FixtureAuthenticator.php');

        $security = $this->app->readYaml('config/packages/security.yaml');
        self::assertArrayHasKey('custom_authenticators', $mainFirewall = $security['security']['firewalls']['main']);
        self::assertSame(['App\Security\FixtureAuthenticator'], $mainFirewall['custom_authenticators']);

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $authenticator = new \App\Security\FixtureAuthenticator();

            self::assertInstanceOf(\Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator::class, $authenticator);

            // the generated body is a commented example: falling through the
            // ?bool return type must raise a TypeError until it is implemented
            try {
                $authenticator->supports(\Symfony\Component\HttpFoundation\Request::create('/'));
                self::fail('supports() returned a value even though its body is still the generated stub.');
            } catch (\TypeError) {
                self::addToAssertionCount(1);
            }
            PHP,
            covers: ['src/Security/FixtureAuthenticator.php'],
        );
    }
}
