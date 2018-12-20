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
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @internal
 */
final class MakeMessage extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:message';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new message and handler')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the message class (e.g. <fg=yellow>SendEmailMessage</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeMessage.txt'))
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
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

        $generator->generateClass(
            $handlerClassNameDetails->getFullName(),
            'message/MessageHandler.tpl.php',
            [
                'message_full_class_name' => $messageClassNameDetails->getFullName(),
                'message_class_name' => $messageClassNameDetails->getShortName(),
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new message class and add the properties you need.',
            '      Then, open thema new message handler and do whatever work you want!',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/messenger.html</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            MessageBusInterface::class,
            'messenger'
        );
    }
}
