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

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\AnnotationDriver as AbstractAnnotationDriver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
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
            $methodCalls = $metadataDriverImpl->getMethodCalls();

            foreach ($methodCalls as $i => [$method, $arguments]) {
                if ('addDriver' !== $method) {
                    continue;
                }

                if ($arguments[0] instanceof Definition) {
                    $class = $arguments[0]->getClass();
                    $namespace = substr($class, 0, strrpos($class, '\\'));

                    if ('Doctrine\ORM\Mapping\Driver' === $namespace ? AnnotationDriver::class !== $class : !is_subclass_of($class, AbstractAnnotationDriver::class)) {
                        continue;
                    }

                    $id = sprintf('.%d_annotation_metadata_driver~%s', $i, ContainerBuilder::hash($arguments));
                    $container->setDefinition($id, $arguments[0]);
                    $arguments[0] = new Reference($id);
                    $methodCalls[$i] = $arguments;
                }

                $isAnnotated = false !== strpos($arguments[0], '_annotation_metadata_driver');
                $annotatedPrefixes[$managerName][] = [
                    $arguments[1],
                    $isAnnotated ? new Reference($arguments[0]) : null,
                ];
            }

            $metadataDriverImpl->setMethodCalls($methodCalls);
        }

        if (null !== $annotatedPrefixes) {
            $container->getDefinition('maker.doctrine_helper')->setArgument(2, $annotatedPrefixes);
        }
    }
}
