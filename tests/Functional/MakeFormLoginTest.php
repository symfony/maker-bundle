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

use Symfony\Bundle\MakerBundle\Maker\Security\MakeFormLogin;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Support\AppRecipes;

#[MakerTest(maker: MakeFormLogin::class, profile: Profiles::MEGA)]
final class MakeFormLoginTest extends MakerTestCase
{
    #[LegacyCase('Security\MakeFormLoginTest::generates_form_login_using_defaults')]
    public function testGeneratesFormLoginUsingDefaults()
    {
        AppRecipes::makeUser($this->app);

        $this->app->runMaker()
            ->answer('Choose a name for the controller class', 'SecurityController')
            ->answer('Do you want to generate a \'/logout\' URL?', 'y')
            ->answer('generate PHPUnit tests', 'n')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('src/Controller/SecurityController.php', 'templates/security/login.html.twig');

        $this->app->assertFileMatchesFixture('src/Controller/SecurityController.php', 'expected/SecurityController.php');
        $this->app->assertFileMatchesFixture('templates/security/login.html.twig', 'expected/login.html.twig');

        $security = $this->app->readYaml('config/packages/security.yaml');
        self::assertSame('app_login', $security['security']['firewalls']['main']['form_login']['login_path']);
        self::assertSame('app_login', $security['security']['firewalls']['main']['form_login']['check_path']);
        self::assertTrue($security['security']['firewalls']['main']['form_login']['enable_csrf']);
        self::assertSame('app_logout', $security['security']['firewalls']['main']['logout']['path']);

        $this->runLoginTest(testLogout: true);
    }

    #[LegacyCase('Security\MakeFormLoginTest::generates_form_login_without_logout')]
    public function testGeneratesFormLoginWithoutLogout()
    {
        AppRecipes::makeUser($this->app);

        $this->app->runMaker()
            ->answer('Choose a name for the controller class', 'SecurityController')
            ->answer('Do you want to generate a \'/logout\' URL?', 'n')
            ->answer('generate PHPUnit tests', 'n')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('src/Controller/SecurityController.php', 'templates/security/login.html.twig');

        $this->app->assertFileMatchesFixture('src/Controller/SecurityController.php', 'expected/SecurityControllerWithoutLogout.php');
        $this->app->assertFileMatchesFixture('templates/security/login.html.twig', 'expected/login_no_logout.html.twig');

        $security = $this->app->readYaml('config/packages/security.yaml');
        self::assertSame('app_login', $security['security']['firewalls']['main']['form_login']['login_path']);
        self::assertSame('app_login', $security['security']['firewalls']['main']['form_login']['check_path']);
        self::assertFalse(isset($security['security']['firewalls']['main']['logout']['path']));

        $this->runLoginTest(testLogout: false);
    }

    #[LegacyCase('Security\MakeFormLoginTest::generates_form_login_with_custom_controller_name')]
    public function testGeneratesFormLoginWithCustomControllerName()
    {
        AppRecipes::makeUser($this->app);

        $this->app->runMaker()
            ->answer('Choose a name for the controller class', 'LoginController')
            ->answer('Do you want to generate a \'/logout\' URL?', 'y')
            ->answer('generate PHPUnit tests', 'n')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('src/Controller/LoginController.php', 'templates/login/login.html.twig');

        $this->app->assertFileMatchesFixture('src/Controller/LoginController.php', 'expected/LoginController.php');
        $this->app->assertFileMatchesFixture('templates/login/login.html.twig', 'expected/login.html.twig');

        $security = $this->app->readYaml('config/packages/security.yaml');
        self::assertSame('app_login', $security['security']['firewalls']['main']['form_login']['login_path']);
        self::assertSame('app_login', $security['security']['firewalls']['main']['form_login']['check_path']);
        self::assertSame('app_logout', $security['security']['firewalls']['main']['logout']['path']);

        $this->runLoginTest(testLogout: true, covers: [
            'src/Controller/LoginController.php',
            'templates/login/login.html.twig',
        ]);
    }

    #[LegacyCase('Security\MakeFormLoginTest::generates_form_login_using_defaults_with_test')]
    public function testGeneratesFormLoginUsingDefaultsWithTest()
    {
        // makes the UserPasswordHasherInterface available in the generated test
        $this->app->copyFixture('FixtureController.php', 'src/Controller/FixtureController.php');

        AppRecipes::makeUser($this->app);

        $this->app->runMaker()
            ->answer('Choose a name for the controller class', 'SecurityController')
            ->answer('Do you want to generate a \'/logout\' URL?', 'y')
            ->answer('generate PHPUnit tests', 'y')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated(
                'src/Controller/SecurityController.php',
                'templates/security/login.html.twig',
                'tests/LoginControllerTest.php',
            );

        $this->app->assertFileMatchesFixture('src/Controller/SecurityController.php', 'expected/SecurityController.php');
        $this->app->assertFileMatchesFixture('templates/security/login.html.twig', 'expected/login.html.twig');

        $this->app->prepareDatabase();
        $this->app->runGeneratedTestFile('tests/LoginControllerTest.php', covers: [
            'tests/LoginControllerTest.php',
            'src/Controller/SecurityController.php',
            'templates/security/login.html.twig',
        ]);
    }

    /**
     * @param list<string>|null $covers
     */
    private function runLoginTest(bool $testLogout, ?array $covers = null): void
    {
        $this->app->renderFixtureTemplate('tests/LoginTest.php.twig', 'tests/LoginTest.php', [
            'testLogout' => $testLogout,
        ]);

        // plaintext password: needed for entities, simplifies overall
        AppRecipes::useTestPlaintextHasher($this->app);

        $this->app->prepareDatabase();

        $this->app->runGeneratedTestFile('tests/LoginTest.php', covers: $covers ?? [
            'src/Controller/SecurityController.php',
            'templates/security/login.html.twig',
        ]);
    }
}
