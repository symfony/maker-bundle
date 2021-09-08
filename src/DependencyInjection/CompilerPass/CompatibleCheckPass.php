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

class CompatibleCheckPass implements CompilerPassInterface
{
    public const DOCTRINE_SUPPORTS_ATTRIBUTE = 'maker.compatible_check.doctrine.supports_attributes';

    public function process(ContainerBuilder $container)
    {
        $container->setParameter(
            self::DOCTRINE_SUPPORTS_ATTRIBUTE,
            $container->hasParameter('doctrine.orm.metadata.attribute.class')
        );
    }
}
