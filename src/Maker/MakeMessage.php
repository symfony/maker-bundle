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
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
        return 'Creates a new message and handler';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the message class (e.g. <fg=yellow>SendEmailMessage</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeMessage.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $command->addArgument('chosen-transport', InputArgument::OPTIONAL);

        $messengerData = [];

        try {
            $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents('config/packages/messenger.yaml'));
            $messengerData = $manipulator->getData();
        } catch (\Exception) {
        }

        if (!isset($messengerData['framework']['messenger']['transports'])) {
            return;
        }

        $transports = array_keys($messengerData['framework']['messenger']['transports']);
        array_unshift($transports, $noTransport = '[no transport]');

        $chosenTransport = $io->choice(
            'Which transport do you want to route your message to?',
            $transports,
            $noTransport
        );

        if ($noTransport !== $chosenTransport) {
            $input->setArgument('chosen-transport', $chosenTransport);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $messageClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Message\\'
        );

        $handlerClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name').'Handler',
            'MessageHandler\\',
            'Handler'
        );

        $generator->generateClass(
            $messageClassNameDetails->getFullName(),
            'message/Message.tpl.php'
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

        if (null !== $chosenTransport = $input->getArgument('chosen-transport')) {
            $this->updateMessengerConfig($generator, $chosenTransport, $messageClassNameDetails->getFullName());
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new message class and add the properties you need.',
            '      Then, open the new message handler and do whatever work you want!',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/messenger.html</>',
        ]);
    }

    private function updateMessengerConfig(Generator $generator, string $chosenTransport, string $messageClass): void
    {
        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents($configFilePath = 'config/packages/messenger.yaml'));
        $messengerData = $manipulator->getData();

        if (!isset($messengerData['framework']['messenger']['routing'])) {
            $messengerData['framework']['messenger']['routing'] = [];
        }

        $messengerData['framework']['messenger']['routing'][$messageClass] = $chosenTransport;

        $manipulator->setData($messengerData);
        $generator->dumpFile($configFilePath, $manipulator->getContents());
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            MessageBusInterface::class,
            'messenger'
        );
    }
}
