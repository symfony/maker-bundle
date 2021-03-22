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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SetDoctrineAnnotatedPrefixesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $annotatedPrefixes = null;

        foreach ($container->findTaggedServiceIds('doctrine.orm.configuration') as $id => $tags) {
            $metadataDriverImpl = null;
            foreach ($container->getDefinition($id)->getMethodCalls() as [$method, $arguments]) {
                if ('setMetadataDriverImpl' === $method) {
                    $metadataDriverImpl = $container->getDefinition($arguments[0]);
                    break;
                }
            }

            if (null === $metadataDriverImpl || !preg_match('/^doctrine\.orm\.(.+)_configuration$/D', $id, $m)) {
                continue;
            }

            $managerName = $m[1];

            foreach ($metadataDriverImpl->getMethodCalls() as [$method, $arguments]) {
                if ('addDriver' === $method) {
                    $isAnnotated = 'doctrine.orm.'.$managerName.'_annotation_metadata_driver' === (string) $arguments[0];
                    $annotatedPrefixes[$managerName][] = [
                        $arguments[1],
                        $isAnnotated ? new Reference($arguments[0]) : null,
                    ];
                }
            }
        }

        if (null !== $annotatedPrefixes) {
            $container->getDefinition('maker.doctrine_helper')->setArgument(2, $annotatedPrefixes);
        }
    }
}
