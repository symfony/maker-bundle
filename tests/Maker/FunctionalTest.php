<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MakerBundle\Command\MakerCommand;
use Symfony\Bundle\MakerBundle\DependencyInjection\CompilerPass\MakeCommandRegistrationPass;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Maker\MakeAuthenticator;
use Symfony\Bundle\MakerBundle\Maker\MakeCommand;
use Symfony\Bundle\MakerBundle\Maker\MakeController;
use Symfony\Bundle\MakerBundle\Maker\MakeCrud;
use Symfony\Bundle\MakerBundle\Maker\MakeEntity;
use Symfony\Bundle\MakerBundle\Maker\MakeFixtures;
use Symfony\Bundle\MakerBundle\Maker\MakeForm;
use Symfony\Bundle\MakerBundle\Maker\MakeFunctionalTest;
use Symfony\Bundle\MakerBundle\Maker\MakeMigration;
use Symfony\Bundle\MakerBundle\Maker\MakeRegistrationForm;
use Symfony\Bundle\MakerBundle\Maker\MakeSerializerEncoder;
use Symfony\Bundle\MakerBundle\Maker\MakeSubscriber;
use Symfony\Bundle\MakerBundle\Maker\MakeTwigExtension;
use Symfony\Bundle\MakerBundle\Maker\MakeUnitTest;
use Symfony\Bundle\MakerBundle\Maker\MakeUser;
use Symfony\Bundle\MakerBundle\Maker\MakeValidator;
use Symfony\Bundle\MakerBundle\Maker\MakeVoter;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Yaml\Yaml;

class FunctionalTest extends MakerTestCase
{
    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * @group functional_group1
     * @dataProvider getCommandTests
     */
    public function testCommands(MakerTestDetails $makerTestDetails)
    {
        $this->executeMakerCommand($makerTestDetails);
    }

    /**
     * @group functional_group2
     * @dataProvider getCommandEntityTests
     */
    public function testEntityCommands(MakerTestDetails $makerTestDetails)
    {
        // entity tests are split into a different method so we can batch on appveyor
        // this solves a weird issue where phpunit would die while running the tests

        $this->executeMakerCommand($makerTestDetails);
    }

    public function getCommandTests()
    {
        yield 'command' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCommand::class),
            [
                // command name
                'app:foo',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCommand'),
        ];

        yield 'command_in_custom_root_namespace' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCommand::class),
            [
                // command name
                'app:foo',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCommandInCustomRootNamespace')
            ->changeRootNamespace('Custom'),
        ];

        yield 'controller_basic' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                'FooBar',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeController')
            ->assert(function (string $output, string $directory) {
                // make sure the template was not configured
                $this->assertContainsCount('created: ', $output, 1);
            }),
        ];

        yield 'controller_with_template_and_base' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                'FooTwig',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeControllerTwig')
            ->addExtraDependencies('twig'),
        ];

        yield 'controller_with_template_no_base' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                'FooTwig',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeControllerTwig')
            ->addExtraDependencies('twig')
            ->deleteFile('templates/base.html.twig'),
        ];

        yield 'controller_without_template' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                'FooNoTemplate',
            ])
            ->setArgumentsString('--no-template')
            ->addExtraDependencies('twig')
            ->assert(function (string $output, string $directory) {
                // make sure the template was not configured
                $this->assertContainsCount('created: ', $output, 1);
                $this->assertStringContainsString('created: src/Controller/FooNoTemplateController.php', $output);
                $this->assertStringNotContainsString('created: templates/foo_no_template/index.html.twig', $output);
            }),
        ];

        yield 'controller_sub_namespace' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                'Admin\\FooBar',
            ])
            ->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/src/Controller/Admin/FooBarController.php');

                $this->assertStringContainsString('created: src/Controller/Admin/FooBarController.php', $output);
            }),
        ];

        yield 'controller_sub_namespace_template' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                'Admin\\FooBar',
            ])
            ->addExtraDependencies('twig')
            ->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/templates/admin/foo_bar/index.html.twig');
            }),
        ];

        yield 'controller_full_custom_namespace' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                '\App\Foo\Bar\CoolController',
            ])
            ->addExtraDependencies('twig')
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('created: src/Foo/Bar/CoolController.php', $output);
                $this->assertStringContainsString('created: templates/foo/bar/cool/index.html.twig', $output);
            }),
        ];

        yield 'fixtures' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeFixtures::class),
            [
                'FooFixtures',
            ])
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('created: src/DataFixtures/FooFixtures.php', $output);
            }),
        ];

        yield 'form_basic' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeForm::class),
            [
                // form name
                'FooBar',
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeForm'),
        ];

        yield 'form_with_entity' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeForm::class),
            [
                // Entity name
                'SourFoodType',
                'SourFood',
            ])
            ->addExtraDependencies('orm')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFormForEntity'),
        ];

        yield 'form_for_non_entity_dto' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeForm::class),
            [
                // Entity name
                'TaskType',
                '\\App\\Form\\Data\\TaskData',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFormForNonEntityDto'),
        ];

        yield 'form_for_sti_entity' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeForm::class),
            [
                // Entity name
                'SourFoodType',
                'SourFood',
            ])
            ->addExtraDependencies('orm')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFormSTIEntity'),
        ];

        yield 'form_for_embebadle_entity' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeForm::class),
            [
                // Entity name
                'FoodType',
                'Food',
            ])
            ->addExtraDependencies('orm')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFormEmbedableEntity'),
        ];

        yield 'functional_maker' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeFunctionalTest::class),
            [
                // functional test class name
                'FooBar',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFunctional'),
        ];

        yield 'functional_with_panther' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeFunctionalTest::class),
            [
                // functional test class name
                'FooBar',
            ])
            ->addExtraDependencies('panther')
            ->setRequiredPhpVersion(70100)
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFunctional'),
        ];

        yield 'subscriber' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeSubscriber::class),
            [
                // subscriber name
                'FooBar',
                // event name
                'kernel.request',
            ]),
        ];

        yield 'subscriber_unknown_event_class' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeSubscriber::class),
            [
                // subscriber name
                'FooBar',
                // event name
                'foo.unknown_event',
            ]),
        ];

        yield 'serializer_encoder' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeSerializerEncoder::class),
            [
                // encoder class name
                'FooBarEncoder',
                // encoder format
                'foobar',
            ]),
        ];

        yield 'twig_extension' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeTwigExtension::class),
            [
                // extension class name
                'FooBar',
            ]),
        ];

        yield 'unit_test' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeUnitTest::class),
            [
                // class name
                'FooBar',
            ]),
        ];

        yield 'validator' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeValidator::class),
            [
                // validator name
                'FooBar',
            ]),
        ];

        yield 'voter' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeVoter::class),
            [
                // voter class name
                'FooBar',
            ]),
        ];

        yield 'auth_empty_one_firewall' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => empty-auth
                    0,
                    // authenticator class name
                    'AppCustomAuthenticator',
                ]
            )
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticator')
            ->assert(
                function (string $output, string $directory) {
                    $this->assertStringContainsString('Success', $output);

                    $fs = new Filesystem();
                    $this->assertTrue($fs->exists(sprintf('%s/src/Security/AppCustomAuthenticator.php', $directory)));

                    $securityConfig = Yaml::parse(file_get_contents(sprintf('%s/config/packages/security.yaml', $directory)));
                    $this->assertEquals(
                        'App\\Security\\AppCustomAuthenticator',
                        $securityConfig['security']['firewalls']['main']['guard']['authenticators'][0]
                    );
                }
            ),
        ];

        yield 'auth_empty_multiple_firewalls' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => empty-auth
                    0,
                    // class name
                    'AppCustomAuthenticator',
                    // firewall name
                    1,
                ]
            )
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorMultipleFirewalls')
            ->assert(
                function (string $output, string $directory) {
                    $this->assertStringContainsString('Success', $output);

                    $securityConfig = Yaml::parse(file_get_contents(sprintf('%s/config/packages/security.yaml', $directory)));
                    $this->assertEquals(
                        'App\\Security\\AppCustomAuthenticator',
                        $securityConfig['security']['firewalls']['second']['guard']['authenticators'][0]
                    );
                }
            ),
        ];

        yield 'auth_empty_existing_authenticator' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => empty-auth
                    0,
                    // class name
                    'AppCustomAuthenticator',
                    // firewall name
                    1,
                ]
            )
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorExistingAuthenticator')
            ->assert(
                function (string $output, string $directory) {
                    $this->assertStringContainsString('Success', $output);

                    $securityConfig = Yaml::parse(file_get_contents(sprintf('%s/config/packages/security.yaml', $directory)));
                    $this->assertEquals(
                        'App\\Security\\AppCustomAuthenticator',
                        $securityConfig['security']['firewalls']['main']['guard']['entry_point']
                    );
                }
            ),
        ];

        yield 'auth_empty_multiple_firewalls_existing_authenticator' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => empty-auth
                    0,
                    // class name
                    'AppCustomAuthenticator',
                    // firewall name
                    1,
                    // entry point
                    1,
                ]
            )
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorMultipleFirewallsExistingAuthenticator')
            ->assert(
                function (string $output, string $directory) {
                    $this->assertStringContainsString('Success', $output);

                    $securityConfig = Yaml::parse(file_get_contents(sprintf('%s/config/packages/security.yaml', $directory)));
                    $this->assertEquals(
                        'App\\Security\\AppCustomAuthenticator',
                        $securityConfig['security']['firewalls']['second']['guard']['entry_point']
                    );
                }
            ),
        ];

        yield 'auth_login_form_user_entity_with_encoder' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    // field name
                    'userEmail',
                    'no',
                ]
            )
            ->addExtraDependencies('doctrine')
            ->addExtraDependencies('twig')
            ->addExtraDependencies('symfony/form')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorLoginFormUserEntity')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->assert(
                function (string $output, string $directory) {
                    $this->assertStringContainsString('Success', $output);

                    $fs = new Filesystem();
                    $this->assertTrue($fs->exists(sprintf('%s/src/Controller/SecurityController.php', $directory)));
                    $this->assertTrue($fs->exists(sprintf('%s/templates/security/login.html.twig', $directory)));
                    $this->assertTrue($fs->exists(sprintf('%s/src/Security/AppCustomAuthenticator.php', $directory)));
                }
            ),
        ];

        yield 'auth_login_form_custom_username_field' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    // user class
                    'App\\Security\\User',
                    // username field => userEmail
                    0,
                    'no',
                ]
            )
            ->addExtraDependencies('doctrine/annotations')
            ->addExtraDependencies('twig')
            ->addExtraDependencies('symfony/form')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorLoginFormCustomUsernameField'),
        ];

        yield 'auth_login_form_user_entity_no_encoder' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    'no',
                ]
            )
            ->addExtraDependencies('doctrine')
            ->addExtraDependencies('twig')
            ->addExtraDependencies('symfony/form')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorLoginFormUserEntityNoEncoder')
            ->configureDatabase()
            ->updateSchemaAfterCommand(),
        ];

        yield 'auth_login_form_user_not_entity_with_encoder' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    // user class
                    'App\Security\User',
                    'no',
                ]
            )
            ->addExtraDependencies('twig')
            ->addExtraDependencies('doctrine/annotations')
            ->addExtraDependencies('symfony/form')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorLoginFormUserNotEntity'),
        ];

        yield 'auth_login_form_user_not_entity_no_encoder' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    // user class
                    'App\Security\User',
                    'no',
                ]
            )
            ->addExtraDependencies('twig')
            ->addExtraDependencies('doctrine/annotations')
            ->addExtraDependencies('symfony/form')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorLoginFormUserNotEntityNoEncoder'),
        ];

        yield 'auth_login_form_existing_controller' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    'no',
                ]
            )
            ->addExtraDependencies('doctrine')
            ->addExtraDependencies('twig')
            ->addExtraDependencies('symfony/form')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorLoginFormExistingController')
            ->configureDatabase()
            ->updateSchemaAfterCommand(),
        ];

        yield 'user_security_entity_with_password' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeUser::class),
            [
                // user class name
                'User',
                'y', // entity
                'email', // identity property
                'y', // with password
                'y', // argon
            ])
            ->addExtraDependencies('doctrine')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeUserEntityPassword')
            ->configureDatabase()
            ->addExtraDependencies('doctrine')
            ->setGuardAuthenticator('main', 'App\\Security\\AutomaticAuthenticator')
            ->setRequiredPhpVersion(70100)
            ->updateSchemaAfterCommand(),
        ];

        yield 'user_security_model_no_password' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeUser::class),
            [
                // user class name (with non-traditional name)
                'FunUser',
                'n', // entity
                'username', // identity property
                'n', // login with password?
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeUserModelNoPassword')
            ->setGuardAuthenticator('main', 'App\\Security\\AutomaticAuthenticator')
            ->setRequiredPhpVersion(70100)
            ->addPostMakeReplacement(
                'src/Security/UserProvider.php',
                'throw new \Exception(\'TODO: fill in refreshUser() inside \'.__FILE__);',
                'return $user;'
            ),
        ];

        yield 'migration_with_changes' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeMigration::class),
            [/* no input */])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeMigration')
            ->configureDatabase(false)
            // doctrine-migrations-bundle only requires doctrine-bundle, which
            // only requires doctrine/dbal. But we're testing with the ORM,
            // so let's install it
            ->addExtraDependencies('doctrine/orm')
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('Success', $output);

                $finder = new Finder();
                $finder->in($directory.'/src/Migrations')
                    ->name('*.php');
                $this->assertCount(1, $finder);

                // see that the exact filename is in the output
                $iterator = $finder->getIterator();
                $iterator->rewind();
                $this->assertStringContainsString(sprintf('"src/Migrations/%s"', $iterator->current()->getFilename()), $output);
            }),
        ];

        yield 'migration_no_changes' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeMigration::class),
            [/* no input */])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeMigration')
            ->configureDatabase()
            // sync the database, so no changes are needed
            ->addExtraDependencies('doctrine/orm')
            ->assert(function (string $output, string $directory) {
                $this->assertNotContains('Success', $output);

                $this->assertStringContainsString('No database changes were detected', $output);
            }),
        ];

        yield 'crud_basic' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCrud::class),
            [
                // entity class name
                'SweetFood',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCrud')
            // need for crud web tests
            ->configureDatabase()
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);
            })
            // workaround for segfault in PHP 7.1 CI :/
            ->setRequiredPhpVersion(70200)
        ];

        yield 'crud_basic_in_custom_root_namespace' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCrud::class),
            [
                // entity class name
                'SweetFood',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCrudInCustomRootNamespace')
            ->changeRootNamespace('Custom')
            // need for crud web tests
            ->configureDatabase()
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);
            })
            // workaround for segfault in PHP 7.1 CI :/
            ->setRequiredPhpVersion(70200)
        ];

        yield 'crud_repository' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCrud::class),
            [
                // entity class name
                'SweetFood',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCrudRepository')
            // need for crud web tests
            ->configureDatabase()
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);
            }),
        ];

        yield 'crud_with_no_base' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCrud::class),
            [
                // entity class name
                'SweetFood',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCrud')
            // need for crud web tests
            ->addExtraDependencies('symfony/css-selector')
            ->configureDatabase()
            ->deleteFile('templates/base.html.twig')
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('created: src/Controller/SweetFoodController.php', $output);
                $this->assertStringContainsString('created: src/Form/SweetFoodType.php', $output);
            })
            // workaround for segfault in PHP 7.1 CI :/
            ->setRequiredPhpVersion(70200)
        ];

        yield 'registration_form_entity_guard_authenticate' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeRegistrationForm::class),
            [
                // user class guessed,
                // username field guessed
                // password guessed
                // firewall name guessed
                '', // yes to add UniqueEntity
                '', // yes authenticate after
                // 1 authenticator will be guessed
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeRegistrationFormEntity')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            // workaround for a strange behavior where, every other
            // test run, the UniqueEntity would not be seen, because
            // the the validation cache was out of date. The cause
            // is currently unknown, so this workaround was added
            ->addPostMakeCommand('php bin/console cache:clear --env=test')
        ];

        // sanity check on all the interactive questions
        yield 'registration_form_no_guessing' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeRegistrationForm::class),
            [
                'App\\Entity\\User',
                'emailAlt', // username field
                'passwordAlt', // password field
                'n', // no UniqueEntity
                '', // yes authenticate after
                'main', // firewall
                '1', // authenticator
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeRegistrationFormNoGuessing'),
        ];

        yield 'registration_form_entity_no_authenticate' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeRegistrationForm::class),
            [
                // all basic data guessed
                'y', // add UniqueEntity
                'n', // no authenticate after
                'app_anonymous', // route name to redirect to
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeRegistrationFormEntity')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            // workaround for strange failure - see test case
            // registration_form_entity_guard_authenticate for details
            ->addPostMakeCommand('php bin/console cache:clear --env=test')
        ];

        yield 'auth_login_form_user_entity_with_encoder_logout' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeAuthenticator::class),
                [
                    // authenticator type => login-form
                    1,
                    // class name
                    'AppCustomAuthenticator',
                    // controller name
                    'SecurityController',
                    // logout support
                    'yes',
                ]
            )
                ->addExtraDependencies('doctrine')
                ->addExtraDependencies('twig')
                ->addExtraDependencies('symfony/form')
                ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeAuthenticatorLoginFormUserEntityLogout')
                ->configureDatabase()
                ->updateSchemaAfterCommand()
                ->assert(
                    function (string $output, string $directory) {
                        $this->assertStringContainsString('Success', $output);

                        $fs = new Filesystem();
                        $this->assertTrue($fs->exists(sprintf('%s/src/Controller/SecurityController.php', $directory)));
                        $this->assertTrue($fs->exists(sprintf('%s/templates/security/login.html.twig', $directory)));
                        $this->assertTrue($fs->exists(sprintf('%s/src/Security/AppCustomAuthenticator.php', $directory)));

                        $securityConfig = Yaml::parse(file_get_contents(sprintf('%s/config/packages/security.yaml', $directory)));
                        $this->assertEquals(
                            'app_logout',
                            $securityConfig['security']['firewalls']['main']['logout']['path']
                        );
                    }
                ),
        ];
    }

    public function getCommandEntityTests()
    {
        yield 'entity_new' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'User',
                // add not additional fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntity')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_new_api_resource' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'User',
                // Mark the entity as an API Platform resource
                'y',
                // add not additional fields
                '',
            ])
            ->addExtraDependencies('api')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntity')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->setRequiredPhpVersion(70100)
            ->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/src/Entity/User.php');

                $content = file_get_contents($directory.'/src/Entity/User.php');
                $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiResource;', $content);
                $this->assertStringContainsString('@ApiResource', $content);
            }),
        ];

        yield 'entity_with_fields' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'User',
                // add not additional fields
                'name',
                'string',
                '255', // length
                // nullable
                'y',
                'createdAt',
                // use default datetime
                '',
                // nullable
                'y',
                // finish adding fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntity')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_updating' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'User',
                // this field already exists
                'firstName',
                // add additional fields
                'lastName',
                'string',
                '', // length (default 255)
                // nullable
                'y',
                // finish adding fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityUpdate')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_many_to_one_simple_with_inverse' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'UserAvatarPhoto',
                // field name
                'user',
                // add a relationship field
                'relation',
                // the target entity
                'User',
                // relation type
                'ManyToOne',
                // nullable
                'n',
                // do you want to generate an inverse relation? (default to yes)
                '',
                // field name on opposite side - use default 'userAvatarPhotos'
                '',
                // orphanRemoval (default to no)
                '',
                // finish adding fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityManyToOne')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_many_to_one_simple_no_inverse' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'UserAvatarPhoto',
                // field name
                'user',
                // add a relationship field
                'relation',
                // the target entity
                'User',
                // relation type
                'ManyToOne',
                // nullable
                'n',
                // do you want to generate an inverse relation? (default to yes)
                'n',
                // finish adding fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityManyToOneNoInverse')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_many_to_one_self_referencing' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'User',
                // field name
                'guardian',
                // add a relationship field
                'relation',
                // the target entity
                'User',
                // relation type
                'ManyToOne',
                // nullable
                'y',
                // do you want to generate an inverse relation? (default to yes)
                '',
                // field name on opposite side
                'dependants',
                // orphanRemoval (default to no)
                '',
                // finish adding fields
                '',
            ])
           ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntitySelfReferencing')
           ->configureDatabase()
           ->updateSchemaAfterCommand()
           ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_exists_in_root' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'Directory',
                // field name
                'parentDirectory',
                // add a relationship field
                'relation',
                // the target entity
                'Directory',
                // relation type
                'ManyToOne',
                // nullable
                'y',
                // do you want to generate an inverse relation? (default to yes)
                '',
                // field name on opposite side
                'childDirectories',
                // orphanRemoval (default to no)
                '',
                // finish adding fields
                '',
            ])
           ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityExistsInRoot')
           ->configureDatabase()
           ->updateSchemaAfterCommand()
           ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_one_to_many_simple' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'User',
                // field name
                'photos',
                // add a relationship field
                'relation',
                // the target entity
                'UserAvatarPhoto',
                // relation type
                'OneToMany',
                // field name on opposite side - use default 'user'
                '',
                // nullable
                'n',
                // orphanRemoval
                'y',
                // finish adding fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityOneToMany')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_many_to_many_simple' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'Course',
                // field name
                'students',
                // add a relationship field
                'relation',
                // the target entity
                'User',
                // relation type
                'ManyToMany',
                // inverse side?
                'y',
                // field name on opposite side - use default 'courses'
                '',
                // finish adding fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityManyToMany')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_many_to_many_simple_in_custom_root_namespace' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'Course',
                // field name
                'students',
                // add a relationship field
                'relation',
                // the target entity
                'User',
                // relation type
                'ManyToMany',
                // inverse side?
                'y',
                // field name on opposite side - use default 'courses'
                '',
                // finish adding fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityManyToManyInCustomNamespace')
            ->changeRootNamespace('Custom')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_one_to_one_simple' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'UserProfile',
                // field name
                'user',
                // add a relationship field
                'relation',
                // the target entity
                'User',
                // relation type
                'OneToOne',
                // nullable
                'n',
                // inverse side?
                'y',
                // field name on opposite side - use default 'userProfile'
                '',
                // finish adding fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityOneToOne')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_many_to_one_vendor_target' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'User',
                // field name
                'userGroup',
                // add a relationship field
                'ManyToOne',
                // the target entity
                'Some\\Vendor\\Group',
                // nullable
                '',
                /*
                 * normally, we ask for the field on the *other* side, but we
                 * do not here, since the other side won't be mapped.
                 */
                // finish adding fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityRelationVendorTarget')
            ->configureDatabase()
            ->addReplacement(
                'composer.json',
                '"App\\\Tests\\\": "tests/",',
                '"App\\\Tests\\\": "tests/",'."\n".'            "Some\\\Vendor\\\": "vendor/some-vendor/src",'
            )
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('updated: src/Entity/User.php', $output);
                $this->assertNotContains('updated: vendor/', $output);

                // sanity checks on the generated code
                $finder = new Finder();
                $finder->in($directory.'/src/Entity')->files()->name('*.php');
                $this->assertCount(1, $finder);

                $this->assertNotContains('inversedBy', file_get_contents($directory.'/src/Entity/User.php'));
            })
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_many_to_many_vendor_target' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'User',
                // field name
                'userGroups',
                // add a relationship field
                'ManyToMany',
                // the target entity
                'Some\Vendor\Group',
                /*
                 * normally, we ask for the field on the *other* side, but we
                 * do not here, since the other side won't be mapped.
                 */
                // finish adding fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityRelationVendorTarget')
            ->configureDatabase()
            ->addReplacement(
                'composer.json',
                '"App\\\Tests\\\": "tests/",',
                '"App\\\Tests\\\": "tests/",'."\n".'            "Some\\\Vendor\\\": "vendor/some-vendor/src",'
            )
            ->assert(function (string $output, string $directory) {
                $this->assertNotContains('updated: vendor/', $output);

                $this->assertNotContains('inversedBy', file_get_contents($directory.'/src/Entity/User.php'));
            })
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_one_to_one_vendor_target' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'User',
                // field name
                'userGroup',
                // add a relationship field
                'OneToOne',
                // the target entity
                'Some\Vendor\Group',
                // nullable,
                '',
                /*
                 * normally, we ask for the field on the *other* side, but we
                 * do not here, since the other side won't be mapped.
                 */
                // finish adding fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityRelationVendorTarget')
            ->configureDatabase()
            ->addReplacement(
                'composer.json',
                '"App\\\Tests\\\": "tests/",',
                '"App\\\Tests\\\": "tests/",'."\n".'            "Some\\\Vendor\\\": "vendor/some-vendor/src",'
            )
            ->assert(function (string $output, string $directory) {
                $this->assertNotContains('updated: vendor/', $output);

                $this->assertNotContains('inversedBy', file_get_contents($directory.'/src/Entity/User.php'));
            })
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_regenerate' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // namespace: use default App\Entity
                '',
            ])
            ->setArgumentsString('--regenerate')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityRegenerate')
            ->configureDatabase(true)
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_regenerate_embeddable_object' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // namespace: use default App\Entity
                '',
            ])
            ->setArgumentsString('--regenerate')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityRegenerateEmbeddableObject')
            ->configureDatabase()
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_regenerate_embeddable' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // namespace: use default App\Entity
                '',
            ])
            ->setArgumentsString('--regenerate --overwrite')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityRegenerateEmbedable')
            ->configureDatabase()
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_regenerate_overwrite' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // namespace: use default App\Entity
                '',
            ])
            ->setArgumentsString('--regenerate --overwrite')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityRegenerateOverwrite')
            ->configureDatabase(false)
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_regenerate_xml' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // namespace: use default App\Entity
                '',
            ])
            ->setArgumentsString('--regenerate')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityRegenerateXml')
            ->addReplacement(
                'config/packages/doctrine.yaml',
                'type: annotation',
                'type: xml'
            )
            ->addReplacement(
                'config/packages/doctrine.yaml',
                "dir: '%kernel.project_dir%/src/Entity'",
                "dir: '%kernel.project_dir%/config/doctrine'"
            )
            ->configureDatabase(false)
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_xml_mapping_error_existing' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                'User',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityXmlMappingError')
            ->addReplacement(
                'config/packages/doctrine.yaml',
                'type: annotation',
                'type: xml'
            )
            ->addReplacement(
                'config/packages/doctrine.yaml',
                "dir: '%kernel.project_dir%/src/Entity'",
                "dir: '%kernel.project_dir%/config/doctrine'"
            )
            ->configureDatabase(false)
            ->setCommandAllowedToFail(true)
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('Only annotation mapping is supported', $output);
            })
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_xml_mapping_error_new_class' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                'UserAvatarPhoto',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityXmlMappingError')
            ->addReplacement(
                'config/packages/doctrine.yaml',
                'type: annotation',
                'type: xml'
            )
            ->addReplacement(
                'config/packages/doctrine.yaml',
                "dir: '%kernel.project_dir%/src/Entity'",
                "dir: '%kernel.project_dir%/config/doctrine'"
            )
            ->configureDatabase(false)
            ->setCommandAllowedToFail(true)
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('Only annotation mapping is supported', $output);
            })
            ->setRequiredPhpVersion(70100),
        ];

        yield 'entity_updating_overwrite' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'User',
                // field name
                'firstName',
                'string',
                '', // length (default 255)
                // nullable
                '',
                // finish adding fields
                '',
            ])
            ->setArgumentsString('--overwrite')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityOverwrite')
            ->setRequiredPhpVersion(70100),
        ];

        // see #192
        yield 'entity_into_sub_namespace_matching_entity' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'Product\\Category',
                // add not additional fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntitySubNamespaceMatchingEntity')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->setRequiredPhpVersion(70100),
        ];
    }

    /**
     * Smoke test to make sure the DI autowiring works and all makers
     * are registered and have the correct arguments.
     */
    public function testWiring()
    {
        $kernel = new FunctionalTestKernel('dev', true);

        $finder = new Finder();
        $finder->in(__DIR__.'/../../src/Maker');

        $application = new Application($kernel);
        foreach ($finder as $file) {
            $class = 'Symfony\Bundle\MakerBundle\Maker\\'.$file->getBasename('.php');

            if (AbstractMaker::class === $class) {
                continue;
            }

            $commandName = $class::getCommandName();
            // if the command does not exist, this will explode
            $command = $application->find($commandName);
            // just a smoke test assert
            $this->assertInstanceOf(MakerCommand::class, $command);
        }
    }

    private function getMakerInstance(string $makerClass): MakerInterface
    {
        if (null === $this->kernel) {
            $this->kernel = new FunctionalTestKernel('dev', true);
            $this->kernel->boot();
        }

        // a cheap way to guess the service id
        $serviceId = $serviceId ?? sprintf('maker.maker.%s', Str::asRouteName((new \ReflectionClass($makerClass))->getShortName()));

        return $this->kernel->getContainer()->get($serviceId);
    }
}

class FunctionalTestKernel extends Kernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    private $testRootDir;

    public function __construct(string $environment, bool $debug)
    {
        $this->testRootDir = sys_get_temp_dir().'/'.uniqid('sf_maker_', true);

        parent::__construct($environment, $debug);
    }

    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new MakerBundle(),
        ];
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $c->setParameter('kernel.secret', 123);
    }

    public function getProjectDir()
    {
        return $this->getRootDir();
    }

    public function getRootDir()
    {
        return $this->testRootDir;
    }

    public function process(ContainerBuilder $container)
    {
        // makes all makers public to help the tests
        foreach ($container->findTaggedServiceIds(MakeCommandRegistrationPass::MAKER_TAG) as $id => $tags) {
            $defn = $container->getDefinition($id);
            $defn->setPublic(true);
        }
    }
}
