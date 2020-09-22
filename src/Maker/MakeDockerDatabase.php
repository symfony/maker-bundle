<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Docker\DockerDatabaseServices;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Util\ComposeFileManipulator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class MakeDockerDatabase extends AbstractMaker
{
    private $fileManager;
    private $composeFilePath;

    /**
     * @var ComposeFileManipulator
     */
    private $composeFileManipulator;

    /**
     * @var string type of database selected by the user
     */
    private $databaseChoice;

    /**
     * @var string Service identifier to be set in docker-compose.yaml
     */
    private $serviceName = 'database';

    /**
     * @var string Version set in docker-compose.yaml for the service. e.g. latest
     */
    private $serviceVersion = 'latest';

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
        $this->composeFilePath = sprintf('%s/docker-compose.yaml', $this->fileManager->getRootDirectory());
    }

    public static function getCommandName(): string
    {
        return 'make:docker:database';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription('Adds a database container to your docker-compose.yaml file')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeDockerDatabase.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $io->section('- Docker Compose Setup-');

        $composeFileContents = '';
        $statusMessage = 'Existing docker-compose.yaml not found: a new one will be generated!';

        if ($this->fileManager->fileExists($this->composeFilePath)) {
            $composeFileContents = $this->fileManager->getFileContents($this->composeFilePath);

            $statusMessage = 'We found your existing docker-compose.yaml: Let\'s update it!';
        }

        $io->text($statusMessage);

        $this->composeFileManipulator = new ComposeFileManipulator($composeFileContents);

        $io->newLine();

        $this->databaseChoice = strtolower($io->choice(
            'Which database service will you be creating?',
            ['MySQL', 'MariaDB', 'Postgres']
        ));

        $io->text([sprintf(
            'For a list of supported versions, check out https://hub.docker.com/_/%s',
            $this->databaseChoice
        )]);

        $this->serviceVersion = $io->ask('What version would you like to use?', DockerDatabaseServices::getSuggestedServiceVersion($this->databaseChoice));

        if ($this->composeFileManipulator->serviceExists($this->serviceName)) {
            $io->comment(sprintf('A <fg=yellow>"%s"</> service is already defined.', $this->serviceName));
            $io->newLine();

            $serviceNameMsg[] = 'If you are using the Symfony Binary, it will expose the connection config for';
            $serviceNameMsg[] = 'this service as environment variables. The name of the service determines the';
            $serviceNameMsg[] = 'name of those environment variables.';
            $serviceNameMsg[] = '';
            $serviceNameMsg[] = 'For example, if you name the service <fg=yellow>database_alt</>, the binary will expose a';
            $serviceNameMsg[] = '<fg=yellow>DATABASE_ALT_URL</> environment variable.';

            $io->text($serviceNameMsg);

            $this->serviceName = $io->ask(sprintf('What name should we call the new %s service? e.g. database', $this->serviceName), null, [Validator::class, 'notBlank']);
        }

        $this->checkForPDOSupport($this->databaseChoice, $io);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $io->newLine();

        $service = DockerDatabaseServices::getDatabaseSkeleton($this->databaseChoice, $this->serviceVersion);

        $this->composeFileManipulator->addDockerService($this->serviceName, $service);
        $this->composeFileManipulator->exposePorts($this->serviceName, DockerDatabaseServices::getDefaultPorts($this->databaseChoice));

        $generator->dumpFile($this->composeFilePath, $this->composeFileManipulator->getDataString());
        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text(sprintf('The new <fg=yellow>"%s"</> service is now ready!', $this->serviceName));
        $io->newLine();

        $ports = DockerDatabaseServices::getDefaultPorts($this->databaseChoice);
        $closing[] = 'Next:';
        $closing[] = sprintf(' A) Run <fg=yellow>docker-compose up -d %s</> to start your database container', $this->serviceName);
        $closing[] = sprintf('    or <fg=yellow>docker-compose up -d</> to start all of them.');
        $closing[] = '';
        $closing[] = ' B) If you are using the Symfony Binary, it will detect the new service automatically.';
        $closing[] = '    Run <fg=yellow>symfony var:export --multiline</> to see the environment variables the binary is exposing.';
        $closing[] = '    These will override any values you have in your .env files.';
        $closing[] = '';
        $closing[] = ' C) Run <fg=yellow>docker-compose stop</> will stop all the containers in docker-compose.yaml.';
        $closing[] = '    <fg=yellow>docker-compose down</> will stop and destroy the containers.';
        $closing[] = '';
        $closing[] = sprintf(
            'Port%s %s will be exposed to %s random port%s on your host machine.',
            1 === \count($ports) ? '' : 's',
            implode(' ', $ports),
            1 === \count($ports) ? 'a' : '',
            1 === \count($ports) ? '' : 's'
        );

        $io->text($closing);
        $io->newLine();
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            Yaml::class,
            'yaml'
        );
    }

    private function checkForPDOSupport(string $databaseType, ConsoleStyle $io): void
    {
        $extension = DockerDatabaseServices::getMissingExtensionName($databaseType);

        if (null !== $extension) {
            $io->note(
                sprintf('Cannot find PHP\'s pdo_%s extension. Be sure it\'s installed & enabled to talk to the database.', $extension)
            );
        }
    }
}
