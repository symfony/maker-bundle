<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('maker');
        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('maker');
        }

        $rootNode
            ->beforeNormalization()
                ->ifTrue(function ($v) { return isset($v['root_namespace']) && !isset($v['namespaces']['root']); })
                ->then(function ($v) {
                    $v['namespaces']['root'] = $v['root_namespace'];

                    return $v;
                })
            ->end()

            ->children()
                ->scalarNode('root_namespace')
                    ->setDeprecated('The child node "%node%" at path "%path%" is deprecated. Please use namespaces.root instead.')
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
                        ->scalarNode('functional_test')->defaultValue('Tests\\')->end()
                        ->scalarNode('repository')->defaultValue('Repository\\')->end()
                        ->scalarNode('security')->defaultValue('Security\\')->end()
                        ->scalarNode('serializer')->defaultValue('Serializer\\')->end()
                        ->scalarNode('subscriber')->defaultValue('EventSubscriber\\')->end()
                        ->scalarNode('twig')->defaultValue('Twig\\')->end()
                        ->scalarNode('unit_test')->defaultValue('Tests\\')->end()
                        ->scalarNode('validator')->defaultValue('Validator\\')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
