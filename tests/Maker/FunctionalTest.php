<?php

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
use Symfony\Bundle\MakerBundle\Maker\MakeSerializerEncoder;
use Symfony\Bundle\MakerBundle\Maker\MakeSubscriber;
use Symfony\Bundle\MakerBundle\Maker\MakeTwigExtension;
use Symfony\Bundle\MakerBundle\Maker\MakeUnitTest;
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
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

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
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCommand')
        ];

        yield 'controller_basic' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                'FooBar',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeController')
            ->assert(function(string $output, string $directory) {
                // make sure the template was not configured
                $this->assertContainsCount('created: ', $output, 1);
            })
        ];

        yield 'controller_with_template_and_base' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                'FooTwig',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeControllerTwig')
            ->addExtraDependencies('twig')
            ->assert(function(string $output, string $directory) {
                $this->assertFileExists($directory.'/templates/foo_twig/index.html.twig');
            })
        ];

        yield 'controller_with_template_no_base' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                'FooTwig',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeControllerTwig')
            ->addExtraDependencies('twig')
            ->addPreMakeCommand('rm templates/base.html.twig')
        ];

        yield 'controller_sub_namespace' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                'Admin\\FooBar',
            ])
            ->assert(function(string $output, string $directory) {
                $this->assertFileExists($directory.'/src/Controller/Admin/FooBarController.php');

                $this->assertContains('created: src/Controller/Admin/FooBarController.php', $output);
            })
        ];

        yield 'controller_sub_namespace_template' => [MakerTestDetails::createTest(
           $this->getMakerInstance(MakeController::class),
           [
               // controller class name
               'Admin\\FooBar',
           ])
            ->addExtraDependencies('twig')
           ->assert(function(string $output, string $directory) {
               $this->assertFileExists($directory.'/templates/admin/foo_bar/index.html.twig');
           })
       ];

        yield 'controller_full_custom_namespace' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            [
                // controller class name
                '\App\Foo\Bar\CoolController',
            ])
            ->addExtraDependencies('twig')
            ->assert(function (string $output, string $directory) {
                $this->assertContains('created: src/Foo/Bar/CoolController.php', $output);
                $this->assertContains('created: templates/foo/bar/cool/index.html.twig', $output);
            })
        ];

        yield 'fixtures' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeFixtures::class),
            [
                'AppFixtures'
            ])
            ->assert(function(string $output, string $directory) {
                $this->assertContains('created: src/DataFixtures/AppFixtures.php', $output);
            })
        ];

        yield 'form_basic' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeForm::class),
            [
                // form name
                'FooBar',
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeForm')
        ];

        yield 'form_with_entity' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeForm::class),
            [
                // Entity name
                'SourFoodType',
                'SourFood',
            ])
            ->addExtraDependencies('orm')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFormForEntity')
        ];

        yield 'functional' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeFunctionalTest::class),
            [
                // functional test class name
                'FooBar',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFunctional')
        ];

        yield 'subscriber' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeSubscriber::class),
            [
                // subscriber name
                'FooBar',
                // event name
                'kernel.request',
            ])
        ];

        yield 'subscriber_unknown_event_class' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeSubscriber::class),
            [
                // subscriber name
                'FooBar',
                // event name
                'foo.unknown_event',
            ])
        ];

        yield 'serializer_encoder' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeSerializerEncoder::class),
            [
                // encoder class name
                'FooBarEncoder',
                // encoder format
                'foobar',
            ])
        ];

        yield 'twig_extension' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeTwigExtension::class),
            [
                // extension class name
                'FooBar',
            ])
        ];

        yield 'unit_test' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeUnitTest::class),
            [
                // class name
                'FooBar',
            ])
        ];

        yield 'validator' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeValidator::class),
            [
                // validator name
                'FooBar',
            ])
        ];

        yield 'voter' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeVoter::class),
            [
                // voter class name
                'FooBar',
            ])
        ];

        yield 'auth_empty' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeAuthenticator::class),
            [
                // class name
                'AppCustomAuthenticator',
            ])
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
            ->assert(function(string $output, string $directory) {
                $this->assertContains('Success', $output);

                $finder = new Finder();
                $finder->in($directory.'/src/Migrations')
                    ->name('*.php');
                $this->assertCount(1, $finder);

                // see that the exact filename is in the output
                $iterator = $finder->getIterator();
                $iterator->rewind();
                $this->assertContains(sprintf('"src/Migrations/%s"', $iterator->current()->getFilename()), $output);
            })
        ];

        yield 'migration_no_changes' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeMigration::class),
            [/* no input */])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeMigration')
            ->configureDatabase()
            // sync the database, so no changes are needed
            ->addExtraDependencies('doctrine/orm')
            ->assert(function(string $output, string $directory) {
                $this->assertNotContains('Success', $output);

                $this->assertContains('No database changes were detected', $output);
            })
        ];

        yield 'crud_basic' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCrud::class),
            [
                // entity class name
                'SweetFood',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCrud')
            // need for crud web tests
            ->addExtraDependencies('symfony/css-selector')
            ->addReplacement(
                'phpunit.xml.dist',
                'mysql://db_user:db_password@127.0.0.1:3306/db_name',
                'sqlite:///%kernel.project_dir%/var/app.db'
            )
            ->addReplacement(
                '.env',
                'mysql://db_user:db_password@127.0.0.1:3306/db_name',
                'sqlite:///%kernel.project_dir%/var/app.db'
            )
            ->addPreMakeCommand('php bin/console doctrine:schema:create --env=test')
            ->assert(function(string $output, string $directory) {
                $this->assertFileExists($directory.'/src/Controller/SweetFoodController.php');
                $this->assertFileExists($directory.'/src/Form/SweetFoodType.php');

                $this->assertContains('created: src/Controller/SweetFoodController.php', $output);
                $this->assertContains('created: src/Form/SweetFoodType.php', $output);
            })
        ];

        yield 'crud_repository' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCrud::class),
            [
                // entity class name
                'SweetFood',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCrudRepository')
            // need for crud web tests
            ->addExtraDependencies('symfony/css-selector')
            ->addReplacement(
                'phpunit.xml.dist',
                'mysql://db_user:db_password@127.0.0.1:3306/db_name',
                'sqlite:///%kernel.project_dir%/var/app.db'
            )
            ->addReplacement(
                '.env',
                'mysql://db_user:db_password@127.0.0.1:3306/db_name',
                'sqlite:///%kernel.project_dir%/var/app.db'
            )
            ->addPreMakeCommand('php bin/console doctrine:schema:create --env=test')
            ->assert(function(string $output, string $directory) {
                 $this->assertFileExists($directory.'/src/Controller/SweetFoodController.php');
                 $this->assertFileExists($directory.'/src/Form/SweetFoodType.php');

                 $this->assertContains('created: src/Controller/SweetFoodController.php', $output);
                 $this->assertContains('created: src/Form/SweetFoodType.php', $output);
            })
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
            ->addReplacement(
                'phpunit.xml.dist',
                'mysql://db_user:db_password@127.0.0.1:3306/db_name',
                'sqlite:///%kernel.project_dir%/var/app.db'
            )
            ->addReplacement(
                '.env',
                'mysql://db_user:db_password@127.0.0.1:3306/db_name',
                'sqlite:///%kernel.project_dir%/var/app.db'
            )
            ->addPreMakeCommand('php bin/console doctrine:schema:create --env=test')
            ->addPreMakeCommand('rm templates/base.html.twig')
            ->assert(function(string $output, string $directory) {
                $this->assertFileExists($directory.'/src/Controller/SweetFoodController.php');
                $this->assertFileExists($directory.'/src/Form/SweetFoodType.php');

                $this->assertContains('created: src/Controller/SweetFoodController.php', $output);
                $this->assertContains('created: src/Form/SweetFoodType.php', $output);
            })
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
                ''
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntity')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
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
                ''
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityUpdate')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
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
                ''
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityManyToOne')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
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
                ''
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityManyToOneNoInverse')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
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
                ''
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityOneToMany')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
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
                ''
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityManyToMany')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
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
                ''
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityOneToOne')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
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
                'Some\Vendor\Group',
                // nullable
                '',
                /*
                 * normally, we ask for the field on the *other* side, but we
                 * do not here, since the other side won't be mapped.
                 */
                // finish adding fields
                ''
            ])
            ->setFixtureFilesPath(__DIR__ . '/../fixtures/MakeEntityRelationVendorTarget')
            ->configureDatabase()
            ->addPreMakeCommand('composer dump-autoload')
            ->addReplacement(
                'composer.json',
                '"App\\\Tests\\\": "tests/",',
                '"App\\\Tests\\\": "tests/",' . "\n" . '            "Some\\\Vendor\\\": "vendor/some-vendor/src",'
            )
            ->assert(function (string $output, string $directory) {
                $this->assertContains('updated: src/Entity/User.php', $output);
                $this->assertNotContains('updated: vendor/', $output);

                // sanity checks on the generated code
                $finder = new Finder();
                $finder->in($directory . '/src/Entity');
                $this->assertEquals(1, count($finder));

                $this->assertNotContains('inversedBy', file_get_contents($directory . '/src/Entity/User.php'));
            })
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
                ''
            ])
            ->setFixtureFilesPath(__DIR__ . '/../fixtures/MakeEntityRelationVendorTarget')
            ->configureDatabase()
            ->addPreMakeCommand('composer dump-autoload')
            ->addReplacement(
                'composer.json',
                '"App\\\Tests\\\": "tests/",',
                '"App\\\Tests\\\": "tests/",'."\n".'            "Some\\\Vendor\\\": "vendor/some-vendor/src",'
            )
            ->assert(function(string $output, string $directory) {
                $this->assertNotContains('updated: vendor/', $output);

                $this->assertNotContains('inversedBy', file_get_contents($directory.'/src/Entity/User.php'));
            })
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
                ''
            ])
            ->setFixtureFilesPath(__DIR__ . '/../fixtures/MakeEntityRelationVendorTarget')
            ->configureDatabase()
            ->addPreMakeCommand('composer dump-autoload')
            ->addReplacement(
                'composer.json',
                '"App\\\Tests\\\": "tests/",',
                '"App\\\Tests\\\": "tests/",'."\n".'            "Some\\\Vendor\\\": "vendor/some-vendor/src",'
            )
            ->assert(function(string $output, string $directory) {
                $this->assertNotContains('updated: vendor/', $output);

                $this->assertNotContains('inversedBy', file_get_contents($directory.'/src/Entity/User.php'));
            })
        ];

        yield 'entity_regenerate' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // namespace: use default App\Entity
                '',
            ])
            ->setArgumentsString('--regenerate')
            ->setFixtureFilesPath(__DIR__ . '/../fixtures/MakeEntityRegenerate')
            ->configureDatabase(true)
        ];

        yield 'entity_regenerate_overwrite' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // namespace: use default App\Entity
                '',
            ])
            ->setArgumentsString('--regenerate --overwrite')
            ->setFixtureFilesPath(__DIR__ . '/../fixtures/MakeEntityRegenerateOverwrite')
            ->configureDatabase(false)
        ];

        yield 'entity_regenerate_xml' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // namespace: use default App\Entity
                '',
            ])
            ->setArgumentsString('--regenerate')
            ->setFixtureFilesPath(__DIR__ . '/../fixtures/MakeEntityRegenerateXml')
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
        ];

        yield 'entity_xml_mapping_error_existing' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                'User',
            ])
            ->setFixtureFilesPath(__DIR__ . '/../fixtures/MakeEntityXmlMappingError')
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
            ->assert(function(string $output, string $directory) {
                $this->assertContains('Only annotation mapping is supported', $output);
            })
        ];

        yield 'entity_xml_mapping_error_new_class' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                'UserAvatarPhoto',
            ])
            ->setFixtureFilesPath(__DIR__ . '/../fixtures/MakeEntityXmlMappingError')
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
            ->assert(function(string $output, string $directory) {
                $this->assertContains('Only annotation mapping is supported', $output);
            })
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
                ''
            ])
            ->setArgumentsString('--overwrite')
            ->setFixtureFilesPath(__DIR__ . '/../fixtures/MakeEntityOverwrite')
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

    public function registerBundles()
    {
        return array(
            new FrameworkBundle(),
            new MakerBundle(),
        );
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $c->setParameter('kernel.secret', 123);
    }

    public function getRootDir()
    {
        return sys_get_temp_dir().'/'.uniqid('sf_maker_', true);
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
