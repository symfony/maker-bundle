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
            ->beforeNormalization()
                ->ifTrue(function ($v) { return isset($v['root_namespace']) && !isset($v['namespaces']['root']); })
                ->then(function ($v) {
                    $v['namespaces']['root'] = $v['root_namespace'];

                    return $v;
                })
            ->end()
            ->children()
                ->scalarNode('root_namespace')
                    ->setDeprecated('symfony/maker-bundle', '2.0', 'The "root_namespace" option is deprecated, use "namespaces.root" instead.')
                    ->defaultNull()
                ->end()
                ->arrayNode('namespaces')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('root')->defaultNull()->end()
                        ->scalarNode('command')->defaultValue('Command\\')->end()
                        ->scalarNode('controller')->defaultValue('Controller\\')->end()
                        ->scalarNode('entity')->defaultValue('Entity\\')->end()
                        ->scalarNode('fixtures')->defaultValue('DataFixtures\\')->end()
                        ->scalarNode('form')->defaultValue('Form\\')->end()
                        ->scalarNode('listener')->defaultValue('EventListener\\')->end()
                        ->scalarNode('message')->defaultValue('Message\\')->end()
                        ->scalarNode('message_handler')->defaultValue('MessageHandler\\')->end()
                        ->scalarNode('middleware')->defaultValue('Middleware\\')->end()
                        ->scalarNode('repository')->defaultValue('Repository\\')->end()
                        ->scalarNode('scheduler')->defaultValue('Scheduler\\')->end()
                        ->scalarNode('security')->defaultValue('Security\\')->end()
                        ->scalarNode('serializer')->defaultValue('Serializer\\')->end()
                        ->scalarNode('subscriber')->defaultValue('EventSubscriber\\')->end()
                        ->scalarNode('test')->defaultValue('Tests\\')->end()
                        ->scalarNode('twig')->defaultValue('Twig\\')->end()
                        ->scalarNode('validator')->defaultValue('Validator\\')->end()
                    ->end()
                ->end()
                ->booleanNode('generate_final_classes')->defaultTrue()->end()
                ->booleanNode('generate_final_entities')->defaultFalse()->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.xml');
        $container->import('../config/makers.xml');

        $container->services()
            ->get('maker.namespaces_helper')
                ->arg(0, $config['namespaces'])
            ->get('maker.template_component_generator')
                ->arg(0, $config['generate_final_classes'])
                ->arg(1, $config['generate_final_entities'])
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
