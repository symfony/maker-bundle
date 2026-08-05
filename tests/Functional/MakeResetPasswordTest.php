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

use Symfony\Bundle\MakerBundle\Maker\MakeResetPassword;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\UsesProfile;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Process\CommandResult;
use Symfony\Bundle\MakerBundle\Tests\Harness\Support\AppRecipes;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;

#[MakerTest(maker: MakeResetPassword::class, profile: Profiles::MEGA)]
final class MakeResetPasswordTest extends MakerTestCase
{
    private const GENERATED_FILES = [
        'src/Controller/ResetPasswordController.php',
        'src/Entity/ResetPasswordRequest.php',
        'src/Form/ChangePasswordFormType.php',
        'src/Form/ResetPasswordRequestFormType.php',
        'src/Repository/ResetPasswordRequestRepository.php',
        'templates/reset_password/check_email.html.twig',
        'templates/reset_password/email.html.twig',
        'templates/reset_password/request.html.twig',
        'templates/reset_password/reset.html.twig',
    ];

    protected function setUp(): void
    {
        // @legacy drop when PHP 8.1 is no longer supported
        if (\PHP_VERSION_ID < 80110) {
            $this->markTestSkipped('symfonycasts/reset-password-bundle is incompatible with PHP 8.1.0 - 8.1.9.');
        }

        parent::setUp();
    }

    #[LegacyCase('MakeResetPasswordTest::it_generates_with_normal_setup')]
    public function testItGeneratesWithNormalSetup()
    {
        AppRecipes::makeUser($this->app);
        $this->app->copyFixture('src/Controller/FixtureController.php', 'src/Controller/FixtureController.php');

        $this->runResetPasswordMaker()
            ->assertOutputContains('Success')
            ->assertCreated(...self::GENERATED_FILES)
            ->assertUpdated('config/packages/reset_password.yaml');

        // the Flex recipe file has comments; the maker fully replaces it
        $this->app->assertFileNotContains('config/packages/reset_password.yaml', '#');
        $config = $this->app->readYaml('config/packages/reset_password.yaml');
        self::assertSame('App\Repository\ResetPasswordRequestRepository', $config['symfonycasts_reset_password']['request_password_repository']);

        $this->prepareResetPasswordRun();
        $this->app->runGeneratedTests('it_generates_with_normal_setup.php');
        $this->runFullResetCycle();
    }

    #[LegacyCase('MakeResetPasswordTest::it_generates_tests')]
    public function testItGeneratesTests()
    {
        $this->app->copyFixture('src/Controller/FixtureController.php', 'src/Controller/FixtureController.php');
        AppRecipes::makeUser($this->app);

        $this->runResetPasswordMaker(generateTests: true)
            ->assertOutputContains('Success')
            ->assertCreated('tests/ResetPasswordControllerTest.php', ...self::GENERATED_FILES);

        $this->prepareResetPasswordRun();
        $this->app->runGeneratedTestFile('tests/ResetPasswordControllerTest.php', covers: [
            'tests/ResetPasswordControllerTest.php',
            ...self::GENERATED_FILES,
        ]);
    }

    #[LegacyCase('MakeResetPasswordTest::it_generates_with_uuid')]
    public function testItGeneratesWithUuid()
    {
        AppRecipes::makeUser($this->app);
        $this->app->copyFixture('src/Controller/FixtureController.php', 'src/Controller/FixtureController.php');

        $this->runResetPasswordMaker(arguments: '--with-uuid')
            ->assertOutputContains('Success')
            ->assertCreated(...self::GENERATED_FILES);

        $this->app->assertFileContains('src/Entity/ResetPasswordRequest.php', 'use Symfony\Component\Uid\Uuid;');
        $this->app->assertFileContains('src/Entity/ResetPasswordRequest.php', '[ORM\CustomIdGenerator(class: \'doctrine.uuid_generator\')]');

        $config = $this->app->readYaml('config/packages/reset_password.yaml');
        self::assertSame('App\Repository\ResetPasswordRequestRepository', $config['symfonycasts_reset_password']['request_password_repository']);

        $this->prepareResetPasswordRun();
        $this->app->runGeneratedTests('it_generates_with_normal_setup.php');
        $this->runFullResetCycle();
    }

    #[LegacyCase('MakeResetPasswordTest::it_generates_with_ulid')]
    public function testItGeneratesWithUlid()
    {
        AppRecipes::makeUser($this->app);
        $this->app->copyFixture('src/Controller/FixtureController.php', 'src/Controller/FixtureController.php');

        $this->runResetPasswordMaker(arguments: '--with-ulid')
            ->assertOutputContains('Success')
            ->assertCreated(...self::GENERATED_FILES);

        $this->app->assertFileContains('src/Entity/ResetPasswordRequest.php', 'use Symfony\Component\Uid\Ulid;');
        $this->app->assertFileContains('src/Entity/ResetPasswordRequest.php', '[ORM\CustomIdGenerator(class: \'doctrine.ulid_generator\')]');

        $this->prepareResetPasswordRun();
        $this->app->runGeneratedTests('it_generates_with_normal_setup.php');
        $this->runFullResetCycle();
    }

    #[LegacyCase('MakeResetPasswordTest::it_generates_with_translator_installed')]
    #[UsesProfile(Profiles::I18N)]
    public function testItGeneratesWithTranslatorInstalled()
    {
        AppRecipes::makeUser($this->app);
        $this->app->copyFixture('src/Controller/FixtureController.php', 'src/Controller/FixtureController.php');

        $this->runResetPasswordMaker(fromEmail: 'victor@symfonycasts.com')
            ->assertOutputContains('Success');

        $this->prepareResetPasswordRun();
        $this->runFullResetCycle();
    }

    #[LegacyCase('MakeResetPasswordTest::it_generates_with_custom_config')]
    public function testItGeneratesWithCustomConfig()
    {
        $this->app->deleteFile('config/packages/reset_password.yaml');
        $this->app->writeYaml('config/packages/custom_reset_password.yaml', [
            'symfonycasts_reset_password' => [
                'request_password_repository' => 'symfonycasts.reset_password.fake_request_repository',
            ],
        ]);

        AppRecipes::makeUser($this->app);
        $this->app->copyFixture('src/Controller/FixtureController.php', 'src/Controller/FixtureController.php');

        $this->runResetPasswordMaker()
            ->assertOutputContains('Success')
            ->assertOutputContains('Just remember to set the request_password_repository in your configuration.')
            ->assertCreated(...self::GENERATED_FILES);

        self::assertFalse($this->app->fileExists('config/packages/reset_password.yaml'));

        // point the custom config at the generated repository so the full
        // cycle can really execute (the fake repository cannot honor tokens)
        $this->app->writeYaml('config/packages/custom_reset_password.yaml', [
            'symfonycasts_reset_password' => [
                'request_password_repository' => 'App\Repository\ResetPasswordRequestRepository',
            ],
        ]);

        $this->prepareResetPasswordRun();
        $this->runFullResetCycle();
    }

    #[LegacyCase('MakeResetPasswordTest::it_amends_configuration')]
    public function testItAmendsConfiguration()
    {
        $config = $this->app->readYaml('config/packages/reset_password.yaml');
        $config['symfonycasts_reset_password']['lifetime'] = 9999;
        $this->app->writeYaml('config/packages/reset_password.yaml', $config);

        AppRecipes::makeUser($this->app);
        $this->app->copyFixture('src/Controller/FixtureController.php', 'src/Controller/FixtureController.php');

        $this->runResetPasswordMaker()
            ->assertOutputContains('Success');

        $config = $this->app->readYaml('config/packages/reset_password.yaml');
        self::assertSame(9999, $config['symfonycasts_reset_password']['lifetime']);
        self::assertSame('App\Repository\ResetPasswordRequestRepository', $config['symfonycasts_reset_password']['request_password_repository']);

        $this->prepareResetPasswordRun();
        $this->runFullResetCycle();
    }

    #[LegacyCase('MakeResetPasswordTest::it_generates_with_custom_user')]
    public function testItGeneratesWithCustomUser()
    {
        AppRecipes::makeUser($this->app, 'emailAddress', 'UserCustom', withPassword: false);
        $this->app->copyFixture('src/Controller/FixtureController.php', 'src/Controller/FixtureController.php');

        $manipulator = new ClassSourceManipulator($this->app->readFile('src/Entity/UserCustom.php'));
        $manipulator->addSetter('myPassword', 'string', true);
        $this->app->writeFile('src/Entity/UserCustom.php', $manipulator->getSourceCode());

        $this->app->runMaker()
            ->answer('holds the email address', 'emailAddress')
            ->answer('set the encoded password', 'setMyPassword')
            ->answer('What route should users be redirected to', 'app_home')
            ->answer('What email address will be used to send reset confirmations?', 'jr@rushlow.dev')
            ->answer('"name" should be associated with that email address', 'SymfonyCasts')
            ->answer('generate PHPUnit tests', 'n')
            ->run()
            ->assertOutputContains('Success');

        $this->app->assertFileContains('src/Controller/ResetPasswordController.php', '$form->get(\'emailAddress\')->getData()');
        $this->app->assertFileContains('src/Controller/ResetPasswordController.php', '\'emailAddress\' => $emailFormData,');
        $this->app->assertFileContains('src/Controller/ResetPasswordController.php', '$user->setMyPassword($passwordHasher->hashPassword($user, $plainPassword));');
        $this->app->assertFileContains('src/Controller/ResetPasswordController.php', '->to((string) $user->getEmailAddress())');
        $this->app->assertFileContains('src/Entity/ResetPasswordRequest.php', 'ORM\ManyToOne');
        $this->app->assertFileContains('src/Form/ResetPasswordRequestFormType.php', '->add(\'emailAddress\', EmailType::class, [');
        $this->app->assertFileContains('templates/reset_password/request.html.twig', '{{ form_row(requestForm.emailAddress) }}');

        $this->prepareResetPasswordRun();
        $this->runFullResetCycle(userShortName: 'UserCustom', emailField: 'emailAddress', hasPassword: false);
    }

    private function runResetPasswordMaker(string $arguments = '', bool $generateTests = false, string $fromEmail = 'jr@rushlow.dev'): CommandResult
    {
        return $this->app->runMaker()
            ->arguments($arguments)
            ->answer('What route should users be redirected to', 'app_home')
            ->answer('What email address will be used to send reset confirmations?', $fromEmail)
            ->answer('"name" should be associated with that email address', 'SymfonyCasts')
            ->answer('generate PHPUnit tests', $generateTests ? 'y' : 'n')
            ->run();
    }

    private function prepareResetPasswordRun(): void
    {
        AppRecipes::useNullMailer($this->app);
        $this->app->prepareDatabase();
    }

    private function runFullResetCycle(string $userShortName = 'User', string $emailField = 'email', bool $hasPassword = true): void
    {
        $this->app->renderFixtureTemplate('tests/reset_password_flow.php.twig', 'tests/ResetPasswordFlowTest.php', [
            'userShortName' => $userShortName,
            'emailField' => $emailField,
            'emailSetter' => 'set'.ucfirst($emailField),
            'email' => 'jr@rushlow.dev',
            'hasPassword' => $hasPassword,
        ]);

        $this->app->runGeneratedTestFile('tests/ResetPasswordFlowTest.php', covers: self::GENERATED_FILES);
    }
}
