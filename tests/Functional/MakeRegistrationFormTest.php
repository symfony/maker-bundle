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

use Symfony\Bundle\MakerBundle\Maker\MakeRegistrationForm;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\UsesProfile;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Support\AppRecipes;

#[MakerTest(maker: MakeRegistrationForm::class, profile: Profiles::MEGA)]
final class MakeRegistrationFormTest extends MakerTestCase
{
    private const BASE_ARTIFACTS = [
        'src/Controller/RegistrationController.php',
        'src/Form/RegistrationFormType.php',
        'templates/registration/register.html.twig',
    ];

    private const VERIFY_ARTIFACTS = [
        'src/Controller/RegistrationController.php',
        'src/Form/RegistrationFormType.php',
        'templates/registration/register.html.twig',
        'src/Security/EmailVerifier.php',
        'templates/registration/confirmation_email.html.twig',
    ];

    #[LegacyCase('MakeRegistrationFormTest::it_generates_registration_with_entity_and_form_login_with_no_login')]
    public function testItGeneratesRegistrationWithEntityAndFormLoginWithNoLogin()
    {
        $this->app->copyFixture('standard_setup', '');
        AppRecipes::makeUser($this->app);

        $this->app->runMaker()
            ->acceptDefault('add a #[UniqueEntity] validation attribute')
            ->answer('send an email to verify the user\'s email address', 'n')
            ->answer('automatically authenticate the user after registration', 'n')
            ->answer('What route should the user be redirected to after registration?', 'app_anonymous')
            ->answer('generate PHPUnit tests', 'n')
            ->run()
            ->assertCreated(...self::BASE_ARTIFACTS);

        $this->app->assertFileMatchesFixture('src/Controller/RegistrationController.php', 'expected/RegistrationControllerNoLogin.php');

        $this->runRegistrationTest('it_generates_registration_with_entity_and_authenticator.php');
    }

    #[LegacyCase('MakeRegistrationFormTest::it_generates_registration_with_entity_and_form_login_with_security_bundle_login')]
    public function testItGeneratesRegistrationWithEntityAndFormLoginWithSecurityBundleLogin()
    {
        $this->app->copyFixture('standard_setup', '');
        AppRecipes::makeUser($this->app);

        $security = $this->app->readYaml('config/packages/security.yaml');
        $security['security']['firewalls']['main']['form_login']['login_path'] = 'app_login';
        $security['security']['firewalls']['main']['form_login']['check_path'] = 'app_login';
        $this->app->writeYaml('config/packages/security.yaml', $security);

        $this->app->runMaker()
            ->acceptDefault('add a #[UniqueEntity] validation attribute')
            ->answer('send an email to verify the user\'s email address', 'n')
            ->acceptDefault('automatically authenticate the user after registration')
            ->answer('generate PHPUnit tests', 'n')
            ->run()
            ->assertCreated(...self::BASE_ARTIFACTS);

        $this->app->assertFileMatchesFixture('src/Controller/RegistrationController.php', 'expected/RegistrationControllerFormLogin.php');

        $this->runRegistrationTest('it_generates_registration_with_entity_and_authenticator.php');
    }

    #[LegacyCase('MakeRegistrationFormTest::it_generates_registration_with_entity_and_custom_authenticator')]
    public function testItGeneratesRegistrationWithEntityAndCustomAuthenticator()
    {
        $this->app->copyFixture('standard_setup', '');
        AppRecipes::makeUser($this->app);

        $security = $this->app->readYaml('config/packages/security.yaml');
        $security['security']['firewalls']['main']['custom_authenticator'] = 'App\\Security\\StubAuthenticator';
        $this->app->writeYaml('config/packages/security.yaml', $security);

        $this->app->runMaker()
            ->acceptDefault('add a #[UniqueEntity] validation attribute')
            ->answer('send an email to verify the user\'s email address', 'n')
            ->acceptDefault('automatically authenticate the user after registration')
            ->answer('generate PHPUnit tests', 'n')
            ->run()
            ->assertCreated(...self::BASE_ARTIFACTS);

        $this->app->assertFileMatchesFixture('src/Controller/RegistrationController.php', 'expected/RegistrationControllerCustomAuthenticator.php');

        $this->runRegistrationTest('it_generates_registration_with_entity_and_authenticator.php');
    }

    #[LegacyCase('MakeRegistrationFormTest::it_generates_registration_form_with_no_guessing')]
    public function testItGeneratesRegistrationFormWithNoGuessing()
    {
        $this->app->copyFixture('standard_setup', '');
        AppRecipes::makeUser($this->app, 'emailAlt');

        $this->app->runMaker()
            ->answerIfAsked('Enter the User class that you want to create during registration', 'App\\Entity\\User')
            ->answer('add a #[UniqueEntity] validation attribute', 'n')
            ->answer('send an email to verify the user\'s email address', 'n')
            ->answer('automatically authenticate the user after registration', 'n')
            ->answer('What route should the user be redirected to after registration?', 'app_anonymous')
            ->answer('generate PHPUnit tests', 'n')
            ->run()
            ->assertCreated(...self::BASE_ARTIFACTS);

        // the registration form must be built around the custom identifier
        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $client = static::createClient();
            $crawler = $client->request('GET', '/register');

            self::assertResponseIsSuccessful();
            self::assertCount(1, $crawler->filter('input[name="registration_form[emailAlt]"]'));
            PHP,
            covers: self::BASE_ARTIFACTS,
            webTestCase: true,
        );
    }

    #[LegacyCase('MakeRegistrationFormTest::it_generates_registration_form_with_entity_no_login')]
    public function testItGeneratesRegistrationFormWithEntityNoLogin()
    {
        $this->app->copyFixture('standard_setup', '');
        AppRecipes::makeUser($this->app);

        $this->app->runMaker()
            ->answer('add a #[UniqueEntity] validation attribute', 'y')
            ->answer('send an email to verify the user\'s email address', 'n')
            ->answer('automatically authenticate the user after registration', 'n')
            ->answer('What route should the user be redirected to after registration?', 'app_anonymous')
            ->answer('generate PHPUnit tests', 'n')
            ->run()
            ->assertCreated(...self::BASE_ARTIFACTS);

        $this->runRegistrationTest('it_generates_registration_with_entity_and_authenticator.php');
    }

    #[LegacyCase('MakeRegistrationFormTest::it_generates_registration_form_with_verification')]
    public function testItGeneratesRegistrationFormWithVerification()
    {
        $this->prepareVerificationApp();

        $this->app->runMaker()
            ->answer('add a #[UniqueEntity] validation attribute', 'n')
            ->answer('send an email to verify the user\'s email address', 'y')
            ->answer('include the user id in the verification link', 'y')
            ->answer('What email address will be used to send registration confirmations?', 'jr@rushlow.dev')
            ->answer('"name" should be associated with that email address', 'SymfonyCasts')
            ->answer('automatically authenticate the user after registration', 'n')
            ->answer('What route should the user be redirected to after registration?', 'app_anonymous')
            ->answer('generate PHPUnit tests', 'n')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated(...self::VERIFY_ARTIFACTS);

        $this->app->assertFileContains('src/Entity/User.php', 'private bool $isVerified = false');

        $this->runRegistrationTest('it_generates_registration_form_with_verification.php', verify: true);
    }

    #[LegacyCase('MakeRegistrationFormTest::it_generates_registration_form_with_verification_and_translator')]
    #[UsesProfile(Profiles::I18N)]
    public function testItGeneratesRegistrationFormWithVerificationAndTranslator()
    {
        $this->prepareVerificationApp();

        $this->app->runMaker()
            ->answer('add a #[UniqueEntity] validation attribute', 'n')
            ->answer('send an email to verify the user\'s email address', 'y')
            ->answer('include the user id in the verification link', 'y')
            ->answer('What email address will be used to send registration confirmations?', 'victor@symfonycasts.com')
            ->answer('"name" should be associated with that email address', 'SymfonyCasts')
            ->answer('automatically authenticate the user after registration', 'n')
            ->answer('What route should the user be redirected to after registration?', 'app_anonymous')
            ->answer('generate PHPUnit tests', 'n')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated(...self::VERIFY_ARTIFACTS);

        $this->runRegistrationTest('it_generates_registration_form_with_verification.php', verify: true);
    }

    #[LegacyCase('MakeRegistrationFormTest::it_generates_registration_form_with_tests')]
    public function testItGeneratesRegistrationFormWithTests()
    {
        $this->app->copyFixture('standard_setup', '');
        AppRecipes::makeUser($this->app);

        $this->app->runMaker()
            ->answer('add a #[UniqueEntity] validation attribute', 'n')
            ->answer('send an email to verify the user\'s email address', 'n')
            ->answer('automatically authenticate the user after registration', 'n')
            ->answer('What route should the user be redirected to after registration?', 'app_anonymous')
            ->answer('generate PHPUnit tests', 'y')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('tests/RegistrationControllerTest.php', ...self::BASE_ARTIFACTS);

        $this->app->prepareDatabase();
        $this->app->runGeneratedTestFile('tests/RegistrationControllerTest.php', covers: [
            'tests/RegistrationControllerTest.php',
            ...self::BASE_ARTIFACTS,
        ]);
    }

    #[LegacyCase('MakeRegistrationFormTest::it_generates_registration_form_with_tests_using_flag')]
    public function testItGeneratesRegistrationFormWithTestsUsingFlag()
    {
        $this->app->copyFixture('standard_setup', '');
        AppRecipes::makeUser($this->app);

        $this->app->runMaker()
            ->arguments('--with-tests')
            ->answer('add a #[UniqueEntity] validation attribute', 'n')
            ->answer('send an email to verify the user\'s email address', 'n')
            ->answer('automatically authenticate the user after registration', 'n')
            ->answer('What route should the user be redirected to after registration?', 'app_anonymous')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('tests/RegistrationControllerTest.php', ...self::BASE_ARTIFACTS);

        $this->app->prepareDatabase();
        $this->app->runGeneratedTestFile('tests/RegistrationControllerTest.php', covers: [
            'tests/RegistrationControllerTest.php',
            ...self::BASE_ARTIFACTS,
        ]);
    }

    #[LegacyCase('MakeRegistrationFormTest::it_generates_registration_form_with_verification_and_with_tests')]
    public function testItGeneratesRegistrationFormWithVerificationAndWithTests()
    {
        $this->prepareVerificationApp();

        $this->app->runMaker()
            ->answer('add a #[UniqueEntity] validation attribute', 'n')
            ->answer('send an email to verify the user\'s email address', 'y')
            ->answer('include the user id in the verification link', 'y')
            ->answer('What email address will be used to send registration confirmations?', 'jr@rushlow.dev')
            ->answer('"name" should be associated with that email address', 'SymfonyCasts')
            ->answer('automatically authenticate the user after registration', 'n')
            ->answer('What route should the user be redirected to after registration?', 'app_anonymous')
            ->answer('generate PHPUnit tests', 'y')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('tests/RegistrationControllerTest.php', ...self::VERIFY_ARTIFACTS);

        $this->app->prepareDatabase();
        $this->app->runGeneratedTestFile('tests/RegistrationControllerTest.php', covers: [
            'tests/RegistrationControllerTest.php',
            'src/Entity/User.php',
            ...self::VERIFY_ARTIFACTS,
        ]);
    }

    private function prepareVerificationApp(): void
    {
        $this->app->copyFixture('standard_setup', '');
        AppRecipes::useNullMailer($this->app);

        AppRecipes::makeUser($this->app);
    }

    private function runRegistrationTest(string $fixtureTestFile, bool $verify = false): void
    {
        $this->app->prepareDatabase();

        // every caller answered yes to UniqueEntity and/or email verification,
        // so the maker also updated the User entity
        $this->app->runGeneratedTests($fixtureTestFile, covers: [
            'src/Entity/User.php',
            ...($verify ? self::VERIFY_ARTIFACTS : self::BASE_ARTIFACTS),
        ]);
    }
}
