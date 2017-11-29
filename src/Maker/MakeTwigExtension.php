<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Twig\Extension\AbstractExtension;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeTwigExtension implements MakerInterface
{
    public static function getCommandName(): string
    {
        return 'make:twig-extension';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new Twig extension class')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the Twig extension class (e.g. <fg=yellow>AppExtension</>).', 'AppExtension')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeTwigExtension.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
    }

    public function getParameters(InputInterface $input): array
    {
        $extensionClassName = Str::asClassName($input->getArgument('name'), 'Extension');
        Validator::validateClassName($extensionClassName);

        return [
            'extension_class_name' => $extensionClassName,
        ];
    }

    public function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/twig/Extension.tpl.php' => 'src/Twig/'.$params['extension_class_name'].'.php',
        ];
    }

    public function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        $io->text([
            'Next: Open your new extension class and start customizing it.',
            'Find the documentation at <fg=yellow>http://symfony.com/doc/current/templating/twig_extension.html</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            AbstractExtension::class,
            'twig'
        );
    }
}
