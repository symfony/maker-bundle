<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Doctrine;


use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Symfony\Bundle\MakerBundle\DependencyBuilder;

/**
 * @internal
 */
final class ODMDependencyBuilder
{
    /**
     * Central method to add dependencies needed for Doctrine ODM.
     */
    public static function buildDependencies(DependencyBuilder $dependencies): void
    {
        $classes = [
            // guarantee DoctrineBundle
            ManagerRegistry::class,
            // guarantee ORM
            Field::class,
        ];

        foreach ($classes as $class) {
            $dependencies->addClassDependency(
                $class,
                'doctrine/mongodb-odm-bundle'
            );
        }
    }
}
