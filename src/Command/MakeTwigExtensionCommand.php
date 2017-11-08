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

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Input\InputArgument;
use Twig\Extension\AbstractExtension;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeTwigExtensionCommand extends AbstractCommand
{
    protected static $defaultName = 'make:twig-extension';

    protected function configure()
    {
        $this
            ->setDescription('Creates a new Twig extension class')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the Twig extension class (e.g. <fg=yellow>AppExtension</>).', 'AppExtension')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeTwigExtension.txt'))
        ;
    }

    protected function getParameters(): array
    {
        $extensionClassName = Str::asClassName($this->input->getArgument('name'), 'Extension');
        Validator::validateClassName($extensionClassName);

        return [
            'extension_class_name' => $extensionClassName,
        ];
    }

    protected function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/twig/Extension.php.txt' => 'src/Twig/'.$params['extension_class_name'].'.php',
        ];
    }

    protected function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        $io->text([
            'Next: Open your new extension class and start customizing it.',
            'Find the documentation at <fg=yellow>http://symfony.com/doc/current/templating/twig_extension.html</>'
        ]);
    }

    protected function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            AbstractExtension::class,
            'twig'
        );
    }
}
