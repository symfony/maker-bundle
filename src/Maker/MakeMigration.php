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

use Doctrine\Bundle\MigrationsBundle\Command\MigrationsDiffDoctrineCommand;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Symfony\Bundle\MakerBundle\ApplicationAwareMakerInterface;
use Symfony\Bundle\MakerBundle\Console\MigrationDiffFilteredOutput;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
final class MakeMigration extends AbstractMaker implements ApplicationAwareMakerInterface
{
    private $projectDir;

    /**
     * @var Application
     */
    private $application;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public static function getCommandName(): string
    {
        return 'make:migration';
    }

    public function setApplication(Application $application)
    {
        $this->application = $application;
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new migration based on database changes')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeMigration.txt'))
        ;

        if (class_exists(MigrationsDiffDoctrineCommand::class)) {
            // support for DoctrineMigrationsBundle 2.x
            $command
                ->addOption('db', null, InputOption::VALUE_REQUIRED, 'The database connection name')
                ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager name')
                ->addOption('shard', null, InputOption::VALUE_REQUIRED, 'The shard connection name')
            ;
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $options = ['doctrine:migrations:diff'];

        // DoctrineMigrationsBundle 2.x support
        if ($input->hasOption('db') && null !== $input->getOption('db')) {
            $options[] = '--db='.$input->getOption('db');
        }
        if ($input->hasOption('em') && null !== $input->getOption('em')) {
            $options[] = '--em='.$input->getOption('em');
        }
        if ($input->hasOption('shard') && null !== $input->getOption('shard')) {
            $options[] = '--shard='.$input->getOption('shard');
        }
        // end 2.x support

        $generateMigrationCommand = $this->application->find('doctrine:migrations:diff');

        $commandOutput = new MigrationDiffFilteredOutput($io->getOutput());
        try {
            $returnCode = $generateMigrationCommand->run(new ArgvInput($options), $commandOutput);

            // non-zero code would ideally mean the internal command has already printed an errror
            // this happens if you "decline" generating a migration when you already
            // have some available
            if (0 !== $returnCode) {
                return $returnCode;
            }

            $migrationOutput = $commandOutput->fetch();

            if (false !== strpos($migrationOutput, 'No changes detected')) {
                $this->noChangesMessage($io);

                return;
            }
        } catch (\Doctrine\Migrations\Generator\Exception\NoChangesDetected $exception) {
            $this->noChangesMessage($io);

            return;
        }

        $this->writeSuccessMessage($io);

        $migrationName = $this->getGeneratedMigrationFilename($migrationOutput);

        $io->text([
            sprintf('Next: Review the new migration <info>%s</info>', $migrationName),
            'Then: Run the migration with <info>php bin/console doctrine:migrations:migrate</info>',
            'See <fg=yellow>https://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html</>',
        ]);
    }

    private function noChangesMessage(ConsoleStyle $io)
    {
        $io->warning([
            'No database changes were detected.',
        ]);
        $io->text([
            'The database schema and the application mapping information are already in sync.',
            '',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            DoctrineMigrationsBundle::class,
            'migrations'
        );
    }

    private function getGeneratedMigrationFilename(string $migrationOutput): string
    {
        preg_match('#"(.*?)"#', $migrationOutput, $matches);

        if (!isset($matches[0])) {
            throw new \Exception('Your migration generated successfully, but an error occurred printing the summary of what occurred.');
        }

        return str_replace($this->projectDir.'/', '', $matches[0]);
    }
}
