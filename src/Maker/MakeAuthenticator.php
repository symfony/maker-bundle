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
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
final class MakeAuthenticator extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:auth';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates an empty Guard authenticator')
            ->addArgument('authenticator-class', InputArgument::OPTIONAL, 'The class name of the authenticator to create (e.g. <fg=yellow>AppCustomAuthenticator</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeAuth.txt'))
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $classNameDetails = $generator->createClassNameDetails(
            $input->getArgument('authenticator-class'),
            'Security\\'
        );

        $generator->generateClass(
            $classNameDetails->getFullName(),
            'authenticator/Empty.tpl.php',
            []
        );
        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Customize your new authenticator.',
            'Then, configure the "guard" key on your firewall to use it.',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            SecurityBundle::class,
            'security'
        );
    }
}
