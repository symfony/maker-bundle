<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle;

use Symfony\Bundle\MakerBundle\DependencyInjection\CompilerPass\MakeCommandRegistrationPass;
use Symfony\Bundle\MakerBundle\DependencyInjection\CompilerPass\RemoveMissingParametersPass;
use Symfony\Bundle\MakerBundle\DependencyInjection\CompilerPass\SetDoctrineAnnotatedPrefixesPass;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
class MakerBundle extends AbstractBundle
{
    protected string $extensionAlias = 'maker';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('root_namespace')->defaultValue('App')->end()
                ->booleanNode('generate_final_classes')->defaultTrue()->end()
                ->booleanNode('generate_final_entities')->defaultFalse()->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.xml');
        $container->import('../config/makers.xml');

        $rootNamespace = trim($config['root_namespace'], '\\');

        $container->services()
            ->get('maker.autoloader_finder')
                ->arg(0, $rootNamespace)
            ->get('maker.generator')
                ->arg(1, $rootNamespace)
            ->get('maker.doctrine_helper')
                ->arg(0, \sprintf('%s\\Entity', $rootNamespace))
            ->get('maker.template_component_generator')
                ->arg(0, $config['generate_final_classes'])
                ->arg(1, $config['generate_final_entities'])
                ->arg(2, $rootNamespace)
        ;

        $builder
            ->registerForAutoconfiguration(MakerInterface::class)
            ->addTag(MakeCommandRegistrationPass::MAKER_TAG)
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        // add a priority so we run before the core command pass
        $container->addCompilerPass(new MakeCommandRegistrationPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
        $container->addCompilerPass(new RemoveMissingParametersPass());
        $container->addCompilerPass(new SetDoctrineAnnotatedPrefixesPass());
    }
}
