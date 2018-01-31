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
        yield 'command' => array(MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCommand::class),
            array(
                // command name
                'app:foo',
            ))
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCommand'),
        );

        yield 'controller' => array(MakerTestDetails::createTest(
            $this->getMakerInstance(MakeController::class),
            array(
                // controller class name
                'FooBar',
            ))
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeController'),
        );

        yield 'entity' => array(MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            array(
                // entity class name
                'TastyFood',
            ))
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
            ->addPostMakeCommand('./bin/console doctrine:schema:create --env=test'),
        );

        yield 'form' => array(MakerTestDetails::createTest(
            $this->getMakerInstance(MakeForm::class),
            array(
                // form name
                'FooBar',
            )),
        );

        yield 'functional' => array(MakerTestDetails::createTest(
            $this->getMakerInstance(MakeFunctionalTest::class),
            array(
                // functional test class name
                'FooBar',
            ))
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFunctional'),
        );

        yield 'subscriber' => array(MakerTestDetails::createTest(
            $this->getMakerInstance(MakeSubscriber::class),
            array(
                // subscriber name
                'FooBar',
                // event name
                'kernel.request',
            )),
        );

        yield 'subscriber_unknown_event_class' => array(MakerTestDetails::createTest(
            $this->getMakerInstance(MakeSubscriber::class),
            array(
                // subscriber name
                'FooBar',
                // event name
                'foo.unknown_event',
            )),
        );

        yield 'serializer_encoder' => array(MakerTestDetails::createTest(
            $this->getMakerInstance(MakeSerializerEncoder::class),
            array(
                // encoder class name
                'FooBarEncoder',
                // encoder format
                'foobar',
            )),
        );

        yield 'twig_extension' => array(MakerTestDetails::createTest(
            $this->getMakerInstance(MakeTwigExtension::class),
            array(
                // extension class name
                'FooBar',
            )),
        );

        yield 'unit_test' => array(MakerTestDetails::createTest(
            $this->getMakerInstance(MakeUnitTest::class),
            array(
                // class name
                'FooBar',
            )),
        );

        yield 'validator' => array(MakerTestDetails::createTest(
            $this->getMakerInstance(MakeValidator::class),
            array(
                // validator name
                'FooBar',
            )),
        );

        yield 'voter' => array(MakerTestDetails::createTest(
            $this->getMakerInstance(MakeVoter::class),
            array(
                // voter class name
                'FooBar',
            )),
        );

        yield 'auth_empty' => array(MakerTestDetails::createTest(
            $this->getMakerInstance(MakeAuthenticator::class),
            array(
                // class name
                'AppCustomAuthenticator',
            )),
        );

        yield 'migration_with_changes' => array(MakerTestDetails::createTest(
            $this->getMakerInstance(MakeMigration::class),
            array(/* no input */))
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
            }),
        );

        yield 'migration_no_changes' => array(MakerTestDetails::createTest(
            $this->getMakerInstance(MakeMigration::class),
            array(/* no input */))
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
            }),
        );

        yield 'crud' => array(MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCrud::class),
            array(
                // entity class name
                'SweetFood',
            ))
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCrud')
            ->addReplacement(
                '.env',
                'mysql://db_user:db_password@127.0.0.1:3306/db_name',
                'sqlite:///%kernel.project_dir%/var/app.db'
            )
            ->addExtraDependencies('symfony/orm-pack')
            ->assert(function(string $output, string $directory) {
                $this->assertContains('Success', $output);
            }),
        );
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
