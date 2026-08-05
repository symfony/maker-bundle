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

use Symfony\Bundle\MakerBundle\Maker\MakeAuthenticator;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Support\AppRecipes;

/**
 * make:auth is deprecated (superseded by the Security\Make* commands) but keeps
 * its coverage until removal.
 */
#[MakerTest(maker: MakeAuthenticator::class, profile: Profiles::MEGA)]
final class MakeAuthenticatorTest extends MakerTestCase
{
    #[LegacyCase('MakeAuthenticatorTest::auth_empty_one_firewall')]
    public function testAuthEmptyOneFirewall()
    {
        $this->app->runMaker()
            ->answer('What style of authentication do you want?', 'Empty authenticator')
            ->answer('class name of the authenticator to create', 'AppCustomAuthenticator')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('src/Security/AppCustomAuthenticator.php')
            ->assertUpdated('config/packages/security.yaml');

        $security = $this->app->readYaml('config/packages/security.yaml');
        self::assertSame(
            'App\\Security\\AppCustomAuthenticator',
            $security['security']['firewalls']['main']['custom_authenticator'],
        );

        $this->assertEmptyAuthenticatorRuns();
    }

    #[LegacyCase('MakeAuthenticatorTest::auth_empty_multiple_firewalls')]
    public function testAuthEmptyMultipleFirewalls()
    {
        $security = $this->app->readYaml('config/packages/security.yaml');
        $security['security']['firewalls']['second']['lazy'] = true;
        $this->app->writeYaml('config/packages/security.yaml', $security);

        $this->app->runMaker()
            ->answer('What style of authentication do you want?', 'Empty authenticator')
            ->answer('class name of the authenticator to create', 'AppCustomAuthenticator')
            ->answer('Which firewall do you want to update?', 'second')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('src/Security/AppCustomAuthenticator.php');

        $security = $this->app->readYaml('config/packages/security.yaml');
        self::assertSame(
            'App\\Security\\AppCustomAuthenticator',
            $security['security']['firewalls']['second']['custom_authenticator'],
        );

        $this->assertEmptyAuthenticatorRuns();
    }

    #[LegacyCase('MakeAuthenticatorTest::auth_empty_existing_authenticator')]
    public function testAuthEmptyExistingAuthenticator()
    {
        $this->app->copyFixture('BlankAuthenticator.php', 'src/Security/BlankAuthenticator.php');

        $security = $this->app->readYaml('config/packages/security.yaml');
        $security['security']['firewalls']['main']['custom_authenticator'] = 'App\Security\BlankAuthenticator';
        $this->app->writeYaml('config/packages/security.yaml', $security);

        $this->app->runMaker()
            ->answer('What style of authentication do you want?', 'Empty authenticator')
            ->answer('class name of the authenticator to create', 'AppCustomAuthenticator')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('src/Security/AppCustomAuthenticator.php');

        $security = $this->app->readYaml('config/packages/security.yaml');
        self::assertSame(
            'App\\Security\\AppCustomAuthenticator',
            $security['security']['firewalls']['main']['custom_authenticator'][1],
        );

        $this->assertEmptyAuthenticatorRuns();
    }

    #[LegacyCase('MakeAuthenticatorTest::auth_empty_multiple_firewalls_existing_authenticator')]
    public function testAuthEmptyMultipleFirewallsExistingAuthenticator()
    {
        $this->app->copyFixture('BlankAuthenticator.php', 'src/Security/BlankAuthenticator.php');

        $security = $this->app->readYaml('config/packages/security.yaml');
        $security['security']['firewalls']['second'] = ['lazy' => true, 'custom_authenticator' => 'App\Security\BlankAuthenticator'];
        $this->app->writeYaml('config/packages/security.yaml', $security);

        $this->app->runMaker()
            ->answer('What style of authentication do you want?', 'Empty authenticator')
            ->answer('class name of the authenticator to create', 'AppCustomAuthenticator')
            ->answer('Which firewall do you want to update?', 'second')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('src/Security/AppCustomAuthenticator.php');

        $security = $this->app->readYaml('config/packages/security.yaml');
        self::assertSame(
            'App\\Security\\AppCustomAuthenticator',
            $security['security']['firewalls']['second']['custom_authenticator'][1],
        );

        $this->assertEmptyAuthenticatorRuns();
    }

    #[LegacyCase('MakeAuthenticatorTest::auth_login_form_user_entity_with_hasher')]
    public function testAuthLoginFormUserEntityWithHasher()
    {
        AppRecipes::makeUser($this->app, 'userEmail');

        $this->app->runMaker()
            ->answer('What style of authentication do you want?', 'Login form authenticator')
            ->answer('class name of the authenticator to create', 'AppCustomAuthenticator')
            ->answer('Choose a name for the controller class', 'SecurityController')
            ->answer('Do you want to generate a \'/logout\' URL?', 'no')
            ->answer('Do you want to support remember me?', 'no')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated(
                'src/Security/AppCustomAuthenticator.php',
                'src/Controller/SecurityController.php',
                'templates/security/login.html.twig',
            );

        $this->runLoginTest('userEmail');
    }

    #[LegacyCase('MakeAuthenticatorTest::auth_login_form_no_entity_custom_username_field')]
    public function testAuthLoginFormNoEntityCustomUsernameField()
    {
        AppRecipes::makeUser($this->app, 'userEmail', isEntity: false);
        $this->app->copyFixture('UserProvider-no-entity.php', 'src/Security/UserProvider.php');

        $this->app->runMaker()
            ->answer('What style of authentication do you want?', 'Login form authenticator')
            ->answer('class name of the authenticator to create', 'AppCustomAuthenticator')
            ->answer('Choose a name for the controller class', 'SecurityController')
            ->answer('Enter the User class that you want to authenticate', 'App\\Security\\User')
            ->answer('will people enter when logging in', 'userEmail')
            ->answer('Do you want to generate a \'/logout\' URL?', 'no')
            ->answer('Do you want to support remember me?', 'no')
            ->run()
            ->assertOutputContains('Success');

        $this->runLoginTest('userEmail', isEntity: false, userClass: 'App\\Security\\User');
    }

    #[LegacyCase('MakeAuthenticatorTest::auth_login_form_user_not_entity_with_hasher')]
    public function testAuthLoginFormUserNotEntityWithHasher()
    {
        AppRecipes::makeUser($this->app, 'email', isEntity: false);
        $this->app->copyFixture('UserProvider-no-entity.php', 'src/Security/UserProvider.php');

        // the fixture UserProvider sets the identifier through setUserEmail()
        $this->app->replaceInFile('src/Security/UserProvider.php', 'setUserEmail(', 'setEmail(');

        $this->app->runMaker()
            ->answer('What style of authentication do you want?', 'Login form authenticator')
            ->answer('class name of the authenticator to create', 'AppCustomAuthenticator')
            ->answer('Choose a name for the controller class', 'SecurityController')
            ->answer('Enter the User class that you want to authenticate', 'App\\Security\\User')
            ->answer('Do you want to generate a \'/logout\' URL?', 'no')
            ->answer('Do you want to support remember me?', 'no')
            ->run()
            ->assertOutputContains('Success');

        $this->runLoginTest('email', isEntity: false, userClass: 'App\\Security\\User');
    }

    #[LegacyCase('MakeAuthenticatorTest::auth_login_form_existing_controller')]
    public function testAuthLoginFormExistingController()
    {
        AppRecipes::makeUser($this->app, 'email');

        $this->app->copyFixture('SecurityController-empty.php', 'src/Controller/SecurityController.php');

        $this->app->runMaker()
            ->answer('What style of authentication do you want?', 'Login form authenticator')
            ->answer('class name of the authenticator to create', 'AppCustomAuthenticator')
            ->answer('Choose a name for the controller class', 'SecurityController')
            ->answer('Do you want to generate a \'/logout\' URL?', 'no')
            ->answer('Do you want to support remember me?', 'no')
            ->run()
            ->assertOutputContains('Success')
            ->assertUpdated('src/Controller/SecurityController.php');

        $this->runLoginTest('email');
    }

    #[LegacyCase('MakeAuthenticatorTest::auth_login_form_user_entity_with_logout')]
    public function testAuthLoginFormUserEntityWithLogout()
    {
        AppRecipes::makeUser($this->app, 'userEmail');

        $this->app->runMaker()
            ->answer('What style of authentication do you want?', 'Login form authenticator')
            ->answer('class name of the authenticator to create', 'AppCustomAuthenticator')
            ->answer('Choose a name for the controller class', 'SecurityController')
            ->answer('Do you want to generate a \'/logout\' URL?', 'yes')
            ->answer('Do you want to support remember me?', 'no')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated(
                'src/Security/AppCustomAuthenticator.php',
                'src/Controller/SecurityController.php',
                'templates/security/login.html.twig',
            );

        $security = $this->app->readYaml('config/packages/security.yaml');
        self::assertSame('app_logout', $security['security']['firewalls']['main']['logout']['path']);

        $this->runLoginTest('userEmail', testLogin: true);
    }

    #[LegacyCase('MakeAuthenticatorTest::auth_login_form_remember_me_via_checkbox')]
    public function testAuthLoginFormRememberMeViaCheckbox()
    {
        AppRecipes::makeUser($this->app, 'userEmail');

        $this->app->runMaker()
            ->answer('What style of authentication do you want?', 'Login form authenticator')
            ->answer('class name of the authenticator to create', 'AppCustomAuthenticator')
            ->answer('Choose a name for the controller class', 'SecurityController')
            ->answer('Do you want to generate a \'/logout\' URL?', 'yes')
            ->answer('Do you want to support remember me?', 'yes')
            ->answer('How should remember me be activated?', 'Activate when the user checks a box')
            ->run()
            ->assertOutputContains('Success');

        $security = $this->app->readYaml('config/packages/security.yaml');
        $firewallMain = $security['security']['firewalls']['main'];
        self::assertSame('%kernel.secret%', $firewallMain['remember_me']['secret']);
        self::assertEquals('604800', $firewallMain['remember_me']['lifetime']);
        self::assertArrayNotHasKey('always_remember_me', $firewallMain['remember_me']);

        $this->runLoginTest('userEmail');
    }

    #[LegacyCase('MakeAuthenticatorTest::auth_login_form_always_remember_me')]
    public function testAuthLoginFormAlwaysRememberMe()
    {
        AppRecipes::makeUser($this->app, 'userEmail');

        $this->app->runMaker()
            ->answer('What style of authentication do you want?', 'Login form authenticator')
            ->answer('class name of the authenticator to create', 'AppCustomAuthenticator')
            ->answer('Choose a name for the controller class', 'SecurityController')
            ->answer('Do you want to generate a \'/logout\' URL?', 'yes')
            ->answer('Do you want to support remember me?', 'yes')
            ->answer('How should remember me be activated?', 'Always activate remember me')
            ->run()
            ->assertOutputContains('Success');

        $security = $this->app->readYaml('config/packages/security.yaml');
        $firewallMain = $security['security']['firewalls']['main'];
        self::assertSame('%kernel.secret%', $firewallMain['remember_me']['secret']);
        self::assertTrue($firewallMain['remember_me']['always_remember_me']);

        $this->runLoginTest('userEmail');
    }

    /**
     * The empty authenticator ships TODO method bodies, but the class must be
     * wired and instantiable inside the container for real.
     */
    private function assertEmptyAuthenticatorRuns(): void
    {
        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $authenticator = new \App\Security\AppCustomAuthenticator();

            self::assertInstanceOf(\Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator::class, $authenticator);

            // the generated body is a TODO: falling through the ?bool return
            // type must raise a TypeError until the user implements it
            try {
                $authenticator->supports(\Symfony\Component\HttpFoundation\Request::create('/'));
                self::fail('supports() returned a value even though its body is still the generated TODO.');
            } catch (\TypeError) {
                self::addToAssertionCount(1);
            }
            PHP,
            covers: ['src/Security/AppCustomAuthenticator.php'],
        );
    }

    private function runLoginTest(string $userIdentifier, bool $isEntity = true, string $userClass = 'App\\Entity\\User', bool $testLogin = false): void
    {
        $this->app->renderFixtureTemplate('tests/LoginFlowTest.php.twig', 'tests/LoginFlowTest.php', [
            'userIdentifier' => $userIdentifier,
            'isEntity' => $isEntity,
            'userClass' => $userClass,
            'testLogin' => $testLogin,
        ]);

        // plaintext password: needed for entities, simplifies overall
        AppRecipes::useTestPlaintextHasher($this->app);

        if ($isEntity) {
            $this->app->prepareDatabase();
        }

        // every login-form case creates or updates all three artifacts
        $this->app->runGeneratedTestFile('tests/LoginFlowTest.php', covers: [
            'src/Security/AppCustomAuthenticator.php',
            'src/Controller/SecurityController.php',
            'templates/security/login.html.twig',
        ]);
    }
}
