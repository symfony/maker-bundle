<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Command;

use Doctrine\Bundle\MigrationsBundle\Command\DoctrineCommand;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class MakeMigrationCommand extends AbstractCommand
{
    protected static $defaultName = 'make:migration';

    protected function configure()
    {
        $this
            ->setDescription('Creates a migration with the diff or an empty if empty is provided.')
            ->addOption('empty', null, InputOption::VALUE_OPTIONAL, 'Generate an empty classes.', false)
            ->addOption('db', null, InputOption::VALUE_REQUIRED, 'The database connection to use for this command.')
            ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command.')
            ->addOption('shard', null, InputOption::VALUE_REQUIRED, 'The shard connection to use for this command.')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeMigration.txt'))
        ;
    }

    protected function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        $io->text([
            'Next: Edit Version(lastVersion) to customize the migration.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html</>'
        ]);
    }

    protected function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            DoctrineCommand::class,
            'migrations'
        );
    }

    protected function getParameters(): array
    {
        return [];
    }

    protected function getFiles(array $params): array
    {
        return [];
    }

    protected function generateCode(OutputInterface $output)
    {
        $isEmpty = $this->input->getOption('empty');
        $options = ['db' => $this->input->getOption('db'), 'em' => $this->input->getOption('em'), 'shard' => $this->input->getOption('shard')];

        try {
            $diffMigrationCommand = $this->getApplication()->find('doctrine:migrations:diff');
            $generateMigrationCommand = $this->getApplication()->find('doctrine:migrations:generate');
        } catch (CommandNotFoundException $e) {
            throw new RuntimeCommandException(sprintf("Missing package %s: to use the migration, run: \n\ncomposer require %s", 'migrations', 'migrations'));
        }

        if (true === $isEmpty) {
            $options['command'] = 'doctrine:migrations:diff';
            $generateMigrationCommand->run(new ArgvInput($options), $output);
        } else {
            $options['command'] = 'doctrine:migrations:generate';
            $diffMigrationCommand->run(new ArgvInput($options), $output);
        }
    }

}