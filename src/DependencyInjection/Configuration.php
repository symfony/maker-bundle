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
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('maker');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('root_namespace')->defaultValue('App\\')->end()
                ->scalarNode('command_namespace')->defaultValue('Command\\')->end()
                ->scalarNode('controller_namespace')->defaultValue('Controller\\')->end()
                ->scalarNode('entity_namespace')->defaultValue('Entity\\')->end()
                ->scalarNode('fixtures_namespace')->defaultValue('DataFixtures\\')->end()
                ->scalarNode('form_namespace')->defaultValue('Form\\')->end()
                ->scalarNode('functional_test_namespace')->defaultValue('Tests\\')->end()
                ->scalarNode('repository_namespace')->defaultValue('Repository\\')->end()
                ->scalarNode('security_namespace')->defaultValue('Security\\')->end()
                ->scalarNode('serializer_namespace')->defaultValue('Serializer\\')->end()
                ->scalarNode('subscriber_namespace')->defaultValue('EventSubscriber\\')->end()
                ->scalarNode('twig_namespace')->defaultValue('Twig\\')->end()
                ->scalarNode('unit_test_namespace')->defaultValue('Tests\\')->end()
                ->scalarNode('validator_namespace')->defaultValue('Validator\\')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
