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
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Messenger\Attribute\AsMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 * @author Nicolas Philippe <nikophil@gmail.com>
 *
 * @internal
 */
final class MakeMessage extends AbstractMaker
{
    public function __construct(private FileManager $fileManager)
    {
    }

    public static function getCommandName(): string
    {
        return 'make:message';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a new message and handler';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the message class (e.g. <fg=yellow>SendEmailMessage</>)')
            ->addOption('transport', mode: InputOption::VALUE_OPTIONAL, description: 'Which transport do you want to route your message to? Omit for none.')
            ->setHelp($this->getHelpFileContents('MakeMessage.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if ($input->getOption('transport')) {
            return;
        }

        $transports = $this->findConfiguredTransports();

        if (!$transports) {
            return;
        }

        $chosenTransport = $io->choice(
            'Which transport do you want to route your message to?',
            [$noTransport = '[no transport]', ...$transports],
            $noTransport
        );

        if ($noTransport !== $chosenTransport) {
            $input->setOption('transport', $chosenTransport);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $chosenTransport = $input->getOption('transport');

        if (null !== $chosenTransport) {
            $configuredTransports = $this->findConfiguredTransports();

            if (!\in_array($chosenTransport, $configuredTransports, true)) {
                $errorMessage = $configuredTransports
                    ? \sprintf('The transport "%s" is not configured in "config/packages/messenger.yaml". Available: "%s".', $chosenTransport, implode('", "', $configuredTransports))
                    : \sprintf('The transport "%s" is not configured in "config/packages/messenger.yaml".', $chosenTransport);

                throw new RuntimeCommandException($errorMessage);
            }
        }

        $messageClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Message\\'
        );

        $handlerClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name').'Handler',
            'MessageHandler\\',
            'Handler'
        );

        $useStatements = new UseStatementGenerator([]);

        if ($chosenTransport) {
            $useStatements->addUseStatement(AsMessage::class);
        }

        $generator->generateClass(
            $messageClassNameDetails->getFullName(),
            'message/Message.tpl.php',
            [
                'use_statements' => $useStatements,
                'transport' => $chosenTransport,
            ]
        );

        $useStatements = new UseStatementGenerator([
            AsMessageHandler::class,
            $messageClassNameDetails->getFullName(),
        ]);

        $generator->generateClass(
            $handlerClassNameDetails->getFullName(),
            'message/MessageHandler.tpl.php',
            [
                'use_statements' => $useStatements,
                'message_class_name' => $messageClassNameDetails->getShortName(),
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new message class and add the properties you need.',
            '      Then, open the new message handler and do whatever work you want!',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/messenger.html</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            MessageBusInterface::class,
            'messenger'
        );
    }

    /**
     * @return string[]
     */
    private function findConfiguredTransports(): array
    {
        try {
            $messengerData = (new YamlSourceManipulator($this->fileManager->getFileContents('config/packages/messenger.yaml')))->getData();
        } catch (\Exception) {
            return [];
        }

        if (!isset($messengerData['framework']['messenger']['transports'])) {
            return [];
        }

        return array_keys($messengerData['framework']['messenger']['transports']);
    }
}
