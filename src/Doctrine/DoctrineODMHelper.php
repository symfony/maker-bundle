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
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\ODM\MongoDB\Mapping\MappingException as ODMMappingException;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata;
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
final class DoctrineODMHelper
{
    public function __construct(
        private string $documentNamespace,
        private ?ManagerRegistry $registry = null,
        private ?array $mappingDriversByPrefix = null,
    ) {
        $this->documentNamespace = trim($documentNamespace, '\\');
    }

    public function getRegistry(): ManagerRegistry
    {
        // this should never happen: we will have checked for the
        // doctrine/mongodb-odm-bundle dependency before calling this
        if (null === $this->registry) {
            throw new \Exception('Somehow the doctrine service is missing. Is doctrine/mongodb-odm-bundle installed?');
        }

        return $this->registry;
    }

    private function isDoctrineInstalled(): bool
    {
        return null !== $this->registry;
    }

    public function getDocumentNamespace(): string
    {
        return $this->documentNamespace;
    }

    public function doesClassUseDriver(string $className, string $driverClass): bool
    {
        try {
            /** @var DocumentManager $dm */
            $dm = $this->getRegistry()->getManagerForClass($className);
        } catch (\ReflectionException) {
            // this exception will be thrown by the registry if the class isn't created yet.
            // an example case is the "make:document" command, which needs to know which driver is used for the class to determine
            // if the class should be generated with attributes or annotations. If this exception is thrown, we will check based on the
            // namespaces for the given $className and compare it with the doctrine configuration to get the correct MappingDriver.

            // extract the new class's namespace from the full $className to check the namespace of the new class against the doctrine configuration.
            $classNameComponents = explode('\\', $className);
            if (1 < \count($classNameComponents)) {
                array_pop($classNameComponents);
            }
            $classNamespace = implode('\\', $classNameComponents);

            return $this->getMappingDriverForNamespace($classNamespace) instanceof $driverClass;
        }

        if (null === $dm) {
            throw new \InvalidArgumentException(sprintf('Cannot find the document manager for class "%s"', $className));
        }

        if (null === $this->mappingDriversByPrefix) {
            $metadataDriver = $dm->getConfiguration()->getMetadataDriverImpl();

            return $metadataDriver instanceof $driverClass;
        }

        $managerName = array_search($dm, $this->getRegistry()->getManagers(), true);

        foreach ($this->mappingDriversByPrefix[$managerName] as [$prefix, $prefixDriver]) {
            if (str_starts_with($className, $prefix)) {
                return $prefixDriver instanceof $driverClass;
            }
        }

        return false;
    }

    public function doesClassUseAttributes(string $className): bool
    {
        return $this->doesClassUseDriver($className, AttributeDriver::class);
    }

    public function getDocumentsForAutocomplete(): array
    {
        $documents = [];

        if ($this->isDoctrineInstalled()) {
            $allMetadata = $this->getMetadata();

            foreach (array_keys($allMetadata) as $classname) {
                $documentClassDetails = new ClassNameDetails($classname, $this->documentNamespace);
                $documents[] = $documentClassDetails->getRelativeName();
            }
        }

        sort($documents);

        return $documents;
    }

    public function getMetadata(string $classOrNamespace = null, bool $disconnected = false): array|ClassMetadata
    {
        // Invalidating the cached AttributeDriver::$classNames to find new Document classes
        foreach ($this->mappingDriversByPrefix ?? [] as $managerName => $prefixes) {
            foreach ($prefixes as [$prefix, $attributeDriver]) {
                if ($attributeDriver instanceof AttributeDriver) {
                    $classNames = (new \ReflectionClass(AttributeDriver::class))->getProperty('classNames');

                    $classNames->setAccessible(true);
                    $classNames->setValue($attributeDriver, null);
                }
            }
        }

        $metadata = [];

        /** @var DocumentManager $dm */
        foreach ($this->getRegistry()->getManagers() as $dm) {
            $cmf = $dm->getMetadataFactory();

            if ($disconnected) {
                try {
                    $loaded = $cmf->getAllMetadata();
                } catch (ODMMappingException|PersistenceMappingException) {
                    $loaded = ($cmf instanceof AbstractClassMetadataFactory) ? $cmf->getLoadedMetadata() : [];
                }

                $cmf = new ClassMetadataFactory();
                $cmf->setDocumentManager($dm);
                $cmf->setConfiguration($dm->getConfiguration());

                foreach ($loaded as $m) {
                    $cmf->setMetadataFor($m->getName(), $m);
                }

                if (null === $this->mappingDriversByPrefix) {
                    // Invalidating the cached AttributeDriver::$classNames to find new Entity classes
                    $metadataDriver = $dm->getConfiguration()->getMetadataDriverImpl();

                    if ($metadataDriver instanceof MappingDriverChain) {
                        foreach ($metadataDriver->getDrivers() as $driver) {
                            if ($driver instanceof AttributeDriver) {
                                $classNames->setValue($driver, null);
                            }
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

                    if (str_starts_with($m->getName(), $classOrNamespace)) {
                        $metadata[$m->getName()] = $m;
                    }
                }
            }
        }

        return $metadata;
    }

    public function isClassAMappedDocument(string $className): bool
    {
        if (!$this->isDoctrineInstalled()) {
            return false;
        }

        return (bool) $this->getMetadata($className);
    }

    /**
     * Determines if the property-type will make the column type redundant.
     *
     * See ClassMetadataInfo::validateAndCompleteTypedFieldMapping()
     */
    public static function canColumnTypeBeInferredByPropertyType(string $columnType, string $propertyType): bool
    {
        return match ($propertyType) {
            '\\'.\DateTime::class => Type::DATE === $columnType,
            '\\'.\DateTimeImmutable::class => Type::DATE_IMMUTABLE === $columnType,
            'array' => Type::HASH === $columnType,
            'bool' => Type::BOOL === $columnType,
            'float' => Type::FLOAT === $columnType,
            'int' => Type::INT === $columnType,
            'string' => Type::STRING === $columnType,
            default => false,
        };
    }

    public static function getPropertyTypeForColumn(string $columnType): ?string
    {
        return match ($columnType) {
            Type::STRING => 'string',
            Type::COLLECTION, Type::HASH => 'array',
            Type::BOOL => 'bool',
            Type::INT, Type::INTID, Type::INTEGER, Type::TIMESTAMP => 'int',
            Type::FLOAT => 'float',
            Type::DATE => '\\'.\DateTimeInterface::class,
            Type::DATE_IMMUTABLE => '\\'.\DateTimeImmutable::class,
            default => null,
        };
    }

    /**
     * Given the string "column type", this returns the "Type::STRING" constant.
     *
     * This is, effectively, a reverse lookup: given the final string, give us
     * the constant to be used in the generated code.
     */
    public static function getTypeConstant(string $columnType): ?string
    {
        $reflection = new \ReflectionClass(Type::class);
        $constants = array_flip($reflection->getConstants());

        if (!isset($constants[$columnType])) {
            return null;
        }

        return sprintf('Type::%s', $constants[$columnType]);
    }

    public function getPotentialCollectionName(string $className): string
    {
        $documentManager = $this->getRegistry()->getManager();

        if (!$documentManager instanceof DocumentManager) {
            throw new \RuntimeException('ObjectManager is not a DocumentManager.');
        }

        return $this->classToCollectionName($className);
    }

    private function classToCollectionName($className)
    {
        if (str_contains($className, '\\')) {
            return substr($className, strrpos($className, '\\') + 1);
        }

        return $className;
    }

    /**
     * this method tries to find the correct MappingDriver for the given namespace/class
     * To determine which MappingDriver belongs to the class we check the prefixes configured in Doctrine and use the
     * prefix that has the closest match to the given $namespace.
     *
     * this helper function is needed to create entities with the configuration of doctrine if they are not yet been registered
     * in the ManagerRegistry
     */
    private function getMappingDriverForNamespace(string $namespace): ?MappingDriver
    {
        $lowestCharacterDiff = null;
        $foundDriver = null;

        foreach ($this->mappingDriversByPrefix ?? [] as $mappings) {
            foreach ($mappings as [$prefix, $driver]) {
                $diff = substr_compare($namespace, $prefix, 0);

                if ($diff >= 0 && (null === $lowestCharacterDiff || $diff < $lowestCharacterDiff)) {
                    $lowestCharacterDiff = $diff;
                    $foundDriver = $driver;
                }
            }
        }

        return $foundDriver;
    }
}
