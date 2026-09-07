<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Maker\Security;

use Symfony\Bundle\MakerBundle\Maker\Security\MakeCustomAuthenticator;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class MakeCustomAuthenticatorTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeCustomAuthenticator::class;
    }

    public static function getTestDetails(): \Generator
    {
        yield 'generates_custom_authenticator' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    'FixtureAuthenticator', // Authenticator Name
                ]);

                self::assertStringContainsString('Success', $output);
                $fixturePath = \dirname(__DIR__, 2).'/fixtures/security/make-custom-authenticator/expected';

                self::assertFileEquals($fixturePath.'/FixtureAuthenticator.php', $runner->getPath('src/Security/FixtureAuthenticator.php'));

                $securityConfig = $runner->readYaml('config/packages/security.yaml');

                self::assertArrayHasKey('custom_authenticators', $mainFirewall = $securityConfig['security']['firewalls']['main']);
                self::assertSame(['App\Security\FixtureAuthenticator'], $mainFirewall['custom_authenticators']);
            }),
        ];

        yield 'generates_custom_authenticator_non_interactively' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], 'FixtureAuthenticator --no-interaction');

                self::assertStringContainsString('Success', $output);

                $fixturePath = \dirname(__DIR__, 2).'/fixtures/security/make-custom-authenticator/expected';
                self::assertFileEquals($fixturePath.'/FixtureAuthenticator.php', $runner->getPath('src/Security/FixtureAuthenticator.php'));

                $securityConfig = $runner->readYaml('config/packages/security.yaml');
                self::assertSame(['App\Security\FixtureAuthenticator'], $securityConfig['security']['firewalls']['main']['custom_authenticators']);
            }),
        ];
    }
}
