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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Benjamin Georgeault <git@wedgesama.fr>
 */
class MakeDecoratorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('maker.maker.make_decorator')) {
            return;
        }

        $container->getDefinition('maker.maker.make_decorator')
            ->replaceArgument(0, ServiceLocatorTagPass::register($container, $ids = $container->getServiceIds()))
            ->replaceArgument(1, $ids)
        ;
    }
}
