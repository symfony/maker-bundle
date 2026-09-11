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
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
 * @deprecated since MakerBundle v1.63.0, use symfony/scheduler recipe instead,
 *
 * @internal
 */
final class MakeSchedule extends AbstractMaker
{
    /** @var string[]|null */
    private ?array $availableMessages = null;

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
            ->addOption('transport-name', mode: InputOption::VALUE_OPTIONAL, description: 'What should we call the new transport? (To be used for the attribute #[AsSchedule(name)])')
            ->addOption('message', mode: InputOption::VALUE_OPTIONAL, description: 'Which message in src/Message should the schedule use? Omit for an empty schedule.')
            ->addOption('schedule-name', mode: InputOption::VALUE_OPTIONAL, description: 'What should we call the new schedule?')
            ->setHelp($this->getHelpFileContents('MakeScheduler.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (!$input->getOption('transport-name')) {
            $input->setOption('transport-name', $io->ask('What should we call the new transport? (To be used for the attribute #[AsSchedule(name)])'));
        }

        if (!$input->getOption('message')) {
            // Loop over existing src/Message/* and ask which message the user would like to schedule
            $availableMessages = ['Empty Schedule', ...$this->findAvailableMessages()];

            // If the count is 1, no other messages were found - don't ask to create a message
            if (1 !== \count($availableMessages)) {
                $selectedMessage = $io->choice('Select which message', $availableMessages);

                if ('Empty Schedule' !== $selectedMessage) {
                    $input->setOption('message', $selectedMessage);
                }
            }
        }

        if (!$input->getOption('schedule-name')) {
            $input->setOption('schedule-name', $io->ask(
                question: 'What should we call the new schedule?',
                default: self::getDefaultScheduleName($input->getOption('message'))
            ));
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        trigger_deprecation('symfony/maker-bundle', 'v1.63.0', '"make:schedule" is deprecated, install the symfony/scheduler recipe instead.');

        if (!class_exists(AsSchedule::class)) {
            $io->writeln('Running composer require symfony/scheduler');
            $process = Process::fromShellCommandline('composer require symfony/scheduler');
            $process->run();
            $io->writeln('Scheduler successfully installed!');
        }

        $message = $input->getOption('message');

        if (null !== $message) {
            $availableMessages = $this->findAvailableMessages();

            if (!\in_array($message, $availableMessages, true)) {
                $errorMessage = $availableMessages
                    ? \sprintf('The message "%s" was not found in "src/Message". Available: "%s".', $message, implode('", "', $availableMessages))
                    : \sprintf('The message "%s" was not found in "src/Message".', $message);

                throw new RuntimeCommandException($errorMessage);
            }
        }

        $scheduleName = $input->getOption('schedule-name') ?: self::getDefaultScheduleName($message);
        $transportName = $input->getOption('transport-name') ?: null;

        if (null !== $transportName) {
            $transportName = Validator::validatePhpStringLiteral($transportName, \sprintf('The "--transport-name" value "%s" cannot contain quotes or backslashes.', $transportName));
        }

        $scheduleClassDetails = $generator->createClassNameDetails(
            $scheduleName,
            'Scheduler\\',
        );

        $useStatements = new UseStatementGenerator([
            AsSchedule::class,
            RecurringMessage::class,
            Schedule::class,
            ScheduleProviderInterface::class,
            CacheInterface::class,
        ]);

        if (null !== $message) {
            $useStatements->addUseStatement('App\\Message\\'.$message);
        }

        $generator->generateClass(
            $scheduleClassDetails->getFullName(),
            'scheduler/Schedule.tpl.php',
            [
                'use_statements' => $useStatements,
                'has_custom_message' => null !== $message,
                'message_class_name' => $message,
                'has_transport_name' => null !== $transportName,
                'transport_name' => $transportName,
            ],
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    /**
     * @return string[]
     */
    private function findAvailableMessages(): array
    {
        if (null !== $this->availableMessages) {
            return $this->availableMessages;
        }

        $messages = [];
        $messageDir = $this->fileManager->getRootDirectory().'/src/Message';

        if ($this->fileManager->fileExists($messageDir)) {
            foreach ($this->finder->in($messageDir)->files() as $file) {
                $messages[] = $file->getFilenameWithoutExtension();
            }
        }

        return $this->availableMessages = $messages;
    }

    private static function getDefaultScheduleName(?string $message): string
    {
        if (null === $message) {
            return 'MainSchedule';
        }

        // We don't want SomeMessageSchedule, so remove the "Message" suffix to give us SomeSchedule
        return \sprintf('%sSchedule', Str::removeSuffix($message, 'Message'));
    }
}
