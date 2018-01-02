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
use Symfony\Bundle\MakerBundle\Maker\MakeEntity;
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
     * @dataProvider getCommandTests
     */
    public function testCommands(MakerTestDetails $makerTestDetails)
    {
        $this->executeMakerCommand($makerTestDetails);
    }

    public function getCommandTests()
    {
        yield 'command' => [MakerTestDetails::createTest(
            MakeCommand::class,
            [
                // command name
                'app:foo',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCommand')
        ];

        yield 'controller' => [MakerTestDetails::createTest(
            MakeController::class,
            [
                // controller class name
                'FooBar',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeController')
        ];

        yield 'entity' => [MakerTestDetails::createTest(
            MakeEntity::class,
            [
                // entity class name
                'TastyFood',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntity')
            ->addReplacement(
                'phpunit.xml.dist',
                'mysql://db_user:db_password@127.0.0.1:3306/db_name',
                'sqlite:///%kernel.project_dir%/var/app.db'
            )
            // currently, we need to replace this in *both* files so we can also
            // run bin/console commands
            ->addReplacement(
                '.env',
                'mysql://db_user:db_password@127.0.0.1:3306/db_name',
                'sqlite:///%kernel.project_dir%/var/app.db'
            )
            ->addPostMakeCommand('./bin/console doctrine:schema:create --env=test')
        ];

        yield 'form' => [MakerTestDetails::createTest(
            MakeForm::class,
            [
                // form name
                'FooBar',
            ])
        ];

        yield 'functional' => [MakerTestDetails::createTest(
            MakeFunctionalTest::class,
            [
                // functional test class name
                'FooBar',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFunctional')
        ];

        yield 'subscriber' => [MakerTestDetails::createTest(
            MakeSubscriber::class,
            [
                // subscriber name
                'FooBar',
                // event name
                'kernel.request',
            ])
        ];

        yield 'subscriber_unknown_event_class' => [MakerTestDetails::createTest(
            MakeSubscriber::class,
            [
                // subscriber name
                'FooBar',
                // event name
                'foo.unknown_event',
            ])
        ];

        yield 'serializer_encoder' => [MakerTestDetails::createTest(
            MakeSerializerEncoder::class,
            [
                // encoder class name
                'FooBarEncoder',
                // encoder format
                'foobar',
            ])
        ];

        yield 'twig_extension' => [MakerTestDetails::createTest(
            MakeTwigExtension::class,
            [
                // extension class name
                'FooBar',
            ])
        ];

        yield 'unit_test' => [MakerTestDetails::createTest(
            MakeUnitTest::class,
            [
                // class name
                'FooBar',
            ])
        ];

        yield 'validator' => [MakerTestDetails::createTest(
            MakeValidator::class,
            [
                // validator name
                'FooBar',
            ])
        ];

        yield 'voter' => [MakerTestDetails::createTest(
            MakeVoter::class,
            [
                // voter class name
                'FooBar',
            ])
        ];

        yield 'auth_empty' => [MakerTestDetails::createTest(
            MakeAuthenticator::class,
            [
                // class name
                'AppCustomAuthenticator',
            ])
        ];

        yield 'migration_with_changes' => [MakerTestDetails::createTest(
            MakeMigration::class,
            [/* no input */])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeMigration')
            ->addReplacement(
                '.env',
                'mysql://db_user:db_password@127.0.0.1:3306/db_name',
                'sqlite:///%kernel.project_dir%/var/app.db'
            )
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
            MakeMigration::class,
            [/* no input */])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeMigration')
            ->addReplacement(
                '.env',
                'mysql://db_user:db_password@127.0.0.1:3306/db_name',
                'sqlite:///%kernel.project_dir%/var/app.db'
            )
            ->addExtraDependencies('doctrine/orm')
            // sync the database, so no changes are needed
            ->addPreMakeCommand('./bin/console doctrine:schema:create --env=test')
            ->assert(function(string $output, string $directory) {
                $this->assertNotContains('Success', $output);

                $this->assertContains('No database changes were detected', $output);
            })
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
}

class FunctionalTestKernel extends Kernel
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
}
