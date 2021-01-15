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

use Doctrine\Common\Persistence\ManagerRegistry as LegacyManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as LegacyClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver as LegacyMappingDriver;
use Doctrine\Common\Persistence\Mapping\MappingException as LegacyPersistenceMappingException;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException as ORMMappingException;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Persistence\Mapping\MappingException as PersistenceMappingException;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Ryan Weaver <ryan@knpuniversity.com>
 * @author Sadicov Vladimir <sadikoff@gmail.com>
 *
 * @internal
 */
final class DoctrineHelper
{
    /**
     * @var string
     */
    private $entityNamespace;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var ManagerRegistry|LegacyManagerRegistry
     */
    public function __construct(string $entityNamespace, $registry = null)
    {
        $this->entityNamespace = trim($entityNamespace, '\\');
        $this->registry = $registry;
    }

    /**
     * @return LegacyManagerRegistry|ManagerRegistry
     */
    public function getRegistry()
    {
        // this should never happen: we will have checked for the
        // DoctrineBundle dependency before calling this
        if (null === $this->registry) {
            throw new \Exception('Somehow the doctrine service is missing. Is DoctrineBundle installed?');
        }

        return $this->registry;
    }

    private function isDoctrineInstalled(): bool
    {
        return null !== $this->registry;
    }

    public function getEntityNamespace(): string
    {
        return $this->entityNamespace;
    }

    /**
     * @return MappingDriver|LegacyMappingDriver|null
     *
     * @throws \Exception
     */
    public function getMappingDriverForClass(string $className)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getRegistry()->getManagerForClass($className);

        if (null === $em) {
            throw new \InvalidArgumentException(sprintf('Cannot find the entity manager for class "%s"', $className));
        }

        $metadataDriver = $em->getConfiguration()->getMetadataDriverImpl();

        if (!$this->isInstanceOf($metadataDriver, MappingDriverChain::class)) {
            return $metadataDriver;
        }

        foreach ($metadataDriver->getDrivers() as $namespace => $driver) {
            if (0 === strpos($className, $namespace)) {
                return $driver;
            }
        }

        return $metadataDriver->getDefaultDriver();
    }

    public function getEntitiesForAutocomplete(): array
    {
        $entities = [];

        if ($this->isDoctrineInstalled()) {
            $allMetadata = $this->getMetadata();

            /* @var ClassMetadata $metadata */
            foreach (array_keys($allMetadata) as $classname) {
                $entityClassDetails = new ClassNameDetails($classname, $this->entityNamespace);
                $entities[] = $entityClassDetails->getRelativeName();
            }
        }

        sort($entities);

        return $entities;
    }

    /**
     * @return array|ClassMetadata|LegacyClassMetadata
     */
    public function getMetadata(string $classOrNamespace = null, bool $disconnected = false)
    {
        $metadata = [];

        /** @var EntityManagerInterface $em */
        foreach ($this->getRegistry()->getManagers() as $em) {
            $cmf = $em->getMetadataFactory();

            if ($disconnected) {
                try {
                    $loaded = $cmf->getAllMetadata();
                } catch (ORMMappingException $e) {
                    $loaded = $this->isInstanceOf($cmf, AbstractClassMetadataFactory::class) ? $cmf->getLoadedMetadata() : [];
                } catch (LegacyPersistenceMappingException $e) {
                    $loaded = $this->isInstanceOf($cmf, AbstractClassMetadataFactory::class) ? $cmf->getLoadedMetadata() : [];
                } catch (PersistenceMappingException $e) {
                    $loaded = $this->isInstanceOf($cmf, AbstractClassMetadataFactory::class) ? $cmf->getLoadedMetadata() : [];
                }

                $cmf = new DisconnectedClassMetadataFactory();
                $cmf->setEntityManager($em);

                foreach ($loaded as $m) {
                    $cmf->setMetadataFor($m->getName(), $m);
                }

                // Invalidating the cached AnnotationDriver::$classNames to find new Entity classes
                $metadataDriver = $em->getConfiguration()->getMetadataDriverImpl();
                if ($this->isInstanceOf($metadataDriver, MappingDriverChain::class)) {
                    foreach ($metadataDriver->getDrivers() as $driver) {
                        if ($this->isInstanceOf($driver, AnnotationDriver::class)) {
                            $classNames = (new \ReflectionObject($driver))->getProperty('classNames');
                            $classNames->setAccessible(true);
                            $classNames->setValue($driver, null);
                            $classNames->setAccessible(false);
                        }
                    }
                }
            }

            foreach ($cmf->getAllMetadata() as $m) {
                if (null === $classOrNamespace) {
                    $metadata[$m->getName()] = $m;
                } else {
                    if ($m->getName() === $classOrNamespace) {
                        return $m;
                    }

                    if (0 === strpos($m->getName(), $classOrNamespace)) {
                        $metadata[$m->getName()] = $m;
                    }
                }
            }
        }

        return $metadata;
    }

    /**
     * @return EntityDetails|null
     */
    public function createDoctrineDetails(string $entityClassName)
    {
        $metadata = $this->getMetadata($entityClassName);

        if ($this->isInstanceOf($metadata, ClassMetadata::class)) {
            return new EntityDetails($metadata);
        }

        return null;
    }

    public function isClassAMappedEntity(string $className): bool
    {
        if (!$this->isDoctrineInstalled()) {
            return false;
        }

        return (bool) $this->getMetadata($className);
    }

    private function isInstanceOf($object, string $class): bool
    {
        if (!\is_object($object)) {
            return false;
        }

        $legacyClass = str_replace('Doctrine\\Persistence\\', 'Doctrine\\Common\\Persistence\\', $class);

        return $object instanceof $class || $object instanceof $legacyClass;
    }

    public function getPotentialTableName(string $className): string
    {
        $entityManager = $this->getRegistry()->getManager();

        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \RuntimeException('ObjectManager is not an EntityManagerInterface.');
        }

        /** @var NamingStrategy $namingStrategy */
        $namingStrategy = $entityManager->getConfiguration()->getNamingStrategy();

        return $namingStrategy->classToTableName($className);
    }

    public function isKeyword(string $name): bool
    {
        /** @var Connection $connection */
        $connection = $this->getRegistry()->getConnection();

        return $connection->getDatabasePlatform()->getReservedKeywordsList()->isKeyword($name);
    }
}
