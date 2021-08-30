<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\DependencyInjection\CompilerPass;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Helps determine which "ManagerRegistry" autowiring alias is available.
 */
class SetDoctrineManagerRegistryClassPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasAlias(ManagerRegistry::class)) {
            $definition = $container->getDefinition('maker.entity_class_generator');
            $definition->addMethodCall('setMangerRegistryClassName', [ManagerRegistry::class]);
        }
    }
}
