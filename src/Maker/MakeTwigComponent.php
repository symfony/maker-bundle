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

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\Compiler\ValidateEnvPlaceholdersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\DependencyInjection\TwigComponentExtension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MakeTwigComponent extends AbstractMaker
{
    private string $namespace = 'Twig\\Components';

    public static function getCommandName(): string
    {
        return 'make:twig-component';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a twig (or live) component';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription(self::getCommandDescription())
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of your twig component (ie <fg=yellow>Notification</>)')
            ->addOption('live', null, InputOption::VALUE_NONE, 'Whether to create a live twig component (requires <fg=yellow>symfony/ux-live-component</>)')
        ;
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(AsTwigComponent::class, 'symfony/ux-twig-component');
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $name = $input->getArgument('name');
        $live = $input->getOption('live');

        if ($live && !class_exists(AsLiveComponent::class)) {
            throw new \RuntimeException('You must install symfony/ux-live-component to create a live component (composer require symfony/ux-live-component)');
        }

        $factory = $generator->createClassNameDetails(
            $name,
            $this->namespace,
        );

        $templatePath = str_replace('\\', '/', $factory->getRelativeNameWithoutSuffix());
        $shortName = str_replace('\\', ':', $factory->getRelativeNameWithoutSuffix());

        $generator->generateClass(
            $factory->getFullName(),
            \sprintf('%s/templates/twig/%s', \dirname(__DIR__, 2), $live ? 'LiveComponent.tpl.php' : 'Component.tpl.php'),
            [
                'live' => $live,
            ]
        );
        $generator->generateTemplate(
            "components/{$templatePath}.html.twig",
            \sprintf('%s/templates/twig/%s', \dirname(__DIR__, 2), 'component_template.tpl.php')
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $io->newLine();
        $io->writeln(" To render the component, use <fg=yellow><twig:{$shortName} /></>.");
        $io->newLine();
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (!$input->getOption('live')) {
            $input->setOption('live', $io->confirm('Make this a live component?', false));
        }

        $application = $command->getApplication();
        assert($application instanceof Application);

        $container = $this->compileContainer($application);
        $config = $this->getConfig($container);

        if (isset($config['defaults'])) {
            $namespace = array_key_first($config['defaults']);
            $this->namespace = substr($namespace, \strpos($namespace, '\\') + 1);
        }
    }

    private function compileContainer(Application $application): ContainerBuilder
    {
        // logic from \Symfony\Bundle\FrameworkBundle\Command\ConfigDebugCommand
        $kernel = clone $application->getKernel();
        $kernel->boot();

        $method = new \ReflectionMethod($kernel, 'buildContainer');
        $container = $method->invoke($kernel);
        $container->getCompiler()->compile($container);

        return $container;
    }

    private function getConfig(ContainerBuilder $container): mixed
    {
        return $container->resolveEnvPlaceholders(
            $container->getParameterBag()->resolveValue(
                $this->getConfigForExtension($container)
            ), true
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfigForExtension(ContainerBuilder $container): array
    {
        $extensionAlias = 'twig_component';

        $extensionConfig = [];
        foreach ($container->getCompilerPassConfig()->getPasses() as $pass) {
            if ($pass instanceof ValidateEnvPlaceholdersPass) {
                $extensionConfig = $pass->getExtensionConfig();
                break;
            }
        }

        if (isset($extensionConfig[$extensionAlias])) {
            return $extensionConfig[$extensionAlias];
        }

        // Fall back to default config if the extension has one
        $extension = new TwigComponentExtension();
        $configs = $container->getExtensionConfig($extensionAlias);

        return (new Processor())->processConfiguration($extension, $configs);
    }
}
