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
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeTwigExtension extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:twig-extension';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new Twig extension class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the Twig extension class (e.g. <fg=yellow>AppExtension</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeTwigExtension.txt'))
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $extensionClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Twig\\Extension\\',
            'Extension'
        );

        $useStatements = new UseStatementGenerator([
            AbstractExtension::class,
            TwigFilter::class,
            TwigFunction::class,
        ]);

        $generator->generateClass(
            $extensionClassNameDetails->getFullName(),
            'twig/Extension.tpl.php',
            ['use_statements' => $useStatements]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new extension class and start customizing it.',
            'Find the documentation at <fg=yellow>http://symfony.com/doc/current/templating/twig_extension.html</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            AbstractExtension::class,
            'twig'
        );
    }
}
