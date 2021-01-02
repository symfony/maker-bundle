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
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Steven Dubois <contact@duboiss.fr>
 * @author Aymeric Gueracague <aymeric.gueracague@gmail.com>
 */
final class MakeMakefile extends AbstractMaker
{
    private $fileManager;
    private $fileSystem;
    private $makefilePath;
    private $hasDoctrine = false;
    private $hasDoctrineFixturesBundle = false;
    private $useSqlite = false;
    private $hasTwig = false;
    private $hasEncore = false;
    private $nodePackagesManager = null;

    public function __construct(FileManager $fileManager)
    {
        $this->fileSystem = new FileSystem();
        $this->fileManager = $fileManager;
        $this->makefilePath = sprintf('%s/Makefile', $this->fileManager->getRootDirectory());
    }

    public static function getCommandName(): string
    {
        return 'make:makefile';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription('Adds a Makefile to your project')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeMakefile.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $io->section('- Makefile Setup -');

        if ($this->fileManager->fileExists($this->makefilePath)) {
            $io->warning('You already have a Makefile, we rename it!');

            $confirmation = new ConfirmationQuestion('This will rename your existing Makefile to Makefile.old - Are you sure?', false);

            if (!$io->askQuestion($confirmation)) {
                exit;
            }

            $this->fileSystem->rename($this->makefilePath, $this->makefilePath.'.old', true);
        }

        if ($this->twigIsInstalled()) {
            $this->hasTwig = true;
            $this->info('Twig detected.', $io);
        }

        if ($this->doctrineIsIntalled()) {
            $this->hasDoctrine = true;
            $this->info('Doctrine detected.', $io);
            if ($this->useSqliteDatabase()) {
                $this->useSqlite = true;
            }
            if ($this->doctrineFixturesBundleIsInstalled()) {
                $this->hasDoctrineFixturesBundle = true;
                $this->info('Doctrine fixtures bundle detected.', $io);
            }
        }

        $encorePath = sprintf('%s/webpack.config.js', $this->fileManager->getRootDirectory());

        if ($this->fileManager->fileExists($encorePath)) {
            $this->hasEncore = true;
            $this->info('Webpack Encore detected.', $io);

            $this->detectNodePackagesManager($io);
        }

        $this->writeSuccessMessage($io);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $generator->generateFile(
            $this->makefilePath,
            'makefile/Makefile.tpl.php',
            [
                'hasTwig' => $this->hasTwig,
                'hasDoctrine' => $this->hasDoctrine,
                'useSqlite' => $this->useSqlite,
                'hasDoctrineFixturesBundle' => $this->hasDoctrineFixturesBundle,
                'hasEncore' => $this->hasEncore,
                'useYarn' => 'yarn' === $this->nodePackagesManager,
            ]
        );
        $generator->writeChanges();
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    private function twigIsInstalled(): bool
    {
        return class_exists('Twig\Environment');
    }

    private function doctrineIsIntalled(): bool
    {
        return \array_key_exists('DATABASE_URL', $_SERVER);
    }

    private function doctrineFixturesBundleIsInstalled(): bool
    {
        return class_exists('Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle');
    }

    private function useSqliteDatabase(): bool
    {
        return 0 === strpos($_SERVER['DATABASE_URL'], 'sqlite');
    }

    private function detectNodePackagesManager(ConsoleStyle $io): void
    {
        $npmLockPath = sprintf('%s/package-lock.json', $this->fileManager->getRootDirectory());
        $yarnLockPath = sprintf('%s/yarn.lock', $this->fileManager->getRootDirectory());

        if (!$this->fileManager->fileExists($npmLockPath)) {
            if (!$this->fileManager->fileExists($yarnLockPath)) {
                $io->warning('package-lock.json or yarn.lock not found.');
                $this->nodePackagesManager = $this->askNodePackagesManager($io);
            } else {
                $this->nodePackagesManager = 'yarn';
            }
        } else {
            $this->nodePackagesManager = 'npm';
        }

        if ($this->fileManager->fileExists($npmLockPath) && $this->fileManager->fileExists($yarnLockPath)) {
            $this->nodePackagesManager = $this->askNodePackagesManager($io);
        }
    }

    private function askNodePackagesManager(ConsoleStyle $io): string
    {
        $choice = null;
        while (null === $choice) {
            $packagesManagers = ['yarn', 'npm'];
            $question = new Question('Which Node packages manager do you want to use (npm or yarn)', 'yarn');
            $question->setAutocompleterValues($packagesManagers);
            $choice = $io->askQuestion($question);

            if (!\in_array($choice, $packagesManagers, true)) {
                $io->error(sprintf('Invalid package manager "%s". Please choose between npm or yarn', $choice));
                $io->writeln('');

                $choice = null;
            }
        }

        return $choice;
    }

    private function info(string $message, ConsoleStyle $io): void
    {
        $io->block($message, 'INFO', 'fg=green', ' ', false);
    }
}
