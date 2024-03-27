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
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class MakeSchedule extends AbstractMaker
{
    private string $scheduleName;
    private ?string $message = null;

    public function __construct(
        private FileManager $fileManager,
        private Finder $finder = new Finder(),
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:schedule';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a scheduler component';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeScheduler.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (!class_exists(AsSchedule::class)) {
            $io->writeln('Running composer require symfony/scheduler');
            $process = Process::fromShellCommandline('composer require symfony/scheduler');
            $process->run();
            $io->writeln('Scheduler successfully installed!');
        }

        // Loop over existing src/Message/* and ask which message the user would like to schedule
        $availableMessages = ['Empty Schedule'];
        $messageDir = $this->fileManager->getRootDirectory().'/src/Message';

        if ($this->fileManager->fileExists($messageDir)) {
            $finder = $this->finder->in($this->fileManager->getRootDirectory().'/src/Message');

            foreach ($finder->files() as $file) {
                $availableMessages[] = $file->getFilenameWithoutExtension();
            }
        }

        $scheduleNameHint = 'MainSchedule';

        // If the count is 1, no other messages were found - don't ask to create a message
        if (1 !== \count($availableMessages)) {
            $selectedMessage = $io->choice('Select which message', $availableMessages);

            if ('Empty Schedule' !== $selectedMessage) {
                $this->message = $selectedMessage;

                // We don't want SomeMessageSchedule, so remove the "Message" suffix to give us SomeSchedule
                $scheduleNameHint = sprintf('%sSchedule', Str::removeSuffix($selectedMessage, 'Message'));
            }
        }

        // Ask the name of the new schedule
        $this->scheduleName = $io->ask(question: 'What should we call the new schedule?', default: $scheduleNameHint);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $scheduleClassDetails = $generator->createClassNameDetails(
            $this->scheduleName,
            'Scheduler\\',
        );

        $useStatements = new UseStatementGenerator([
            AsSchedule::class,
            RecurringMessage::class,
            Schedule::class,
            ScheduleProviderInterface::class,
            CacheInterface::class,
        ]);

        if (null !== $this->message) {
            $useStatements->addUseStatement('App\\Message\\'.$this->message);
        }

        $generator->generateClass(
            $scheduleClassDetails->getFullName(),
            'scheduler/Schedule.tpl.php',
            [
                'use_statements' => $useStatements,
                'has_custom_message' => null !== $this->message,
                'message_class_name' => $this->message,
            ],
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }
}
