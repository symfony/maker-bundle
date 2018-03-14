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

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;

/**
 * Simpler version of DoctrineBundle's DisconnectedMetadataFactory, to
 * avoid PSR-4 issues.
 *
 * @internal
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
final class DoctrineMetadataFactory
{
    private $registry;

    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry A ManagerRegistry instance
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $namespace
     *
     * @return array|ClassMetadata[]
     */
    public function getMetadataForNamespace($namespace)
    {
        $metadata = [];
        foreach ($this->getAllMetadata() as $m) {
            if (0 === strpos($m->name, $namespace)) {
                $metadata[] = $m;
            }
        }

        return $metadata;
    }

    public function getMetadataForClass(string $entity): ?ClassMetadata
    {
        foreach ($this->registry->getManagers() as $em) {
            $cmf = new DisconnectedClassMetadataFactory();
            $cmf->setEntityManager($em);

            if (!$cmf->isTransient($entity)) {
                return $cmf->getMetadataFor($entity);
            }
        }

        return null;
    }

    public function getMappingDriverForClass(string $className): ?MappingDriver
    {
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass($className);

        if (null === $em) {
            throw new \InvalidArgumentException(sprintf('Cannot find the entity manager for class "%s"', $className));
        }

        $metadataDriver = $em->getConfiguration()->getMetadataDriverImpl();

        if (!$metadataDriver instanceof MappingDriverChain) {
            return $metadataDriver;
        }

        foreach ($metadataDriver->getDrivers() as $namespace => $driver) {
            if (0 === strpos($className, $namespace)) {
                return $driver;
            }
        }

        return $metadataDriver->getDefaultDriver();
    }

    /**
     * @return array
     */
    public function getAllMetadata()
    {
        $metadata = [];
        foreach ($this->registry->getManagers() as $em) {
            $cmf = new DisconnectedClassMetadataFactory();
            $cmf->setEntityManager($em);
            foreach ($cmf->getAllMetadata() as $m) {
                $metadata[] = $m;
            }
        }

        return $metadata;
    }
}
