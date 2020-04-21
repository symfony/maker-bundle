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
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

/**
 * @internal
 */
final class EntityClassGenerator
{
    private $generator;
    private $doctrineHelper;
    private $managerRegistryClassName = LegacyManagerRegistry::class;

    public function __construct(Generator $generator, DoctrineHelper $doctrineHelper)
    {
        $this->generator = $generator;
        $this->doctrineHelper = $doctrineHelper;
    }

    public function generateEntityClass(ClassNameDetails $entityClassDetails, bool $apiResource, bool $withPasswordUpgrade = false): string
    {
        $repoClassDetails = $this->generator->createClassNameDetails(
            $entityClassDetails->getRelativeName(),
            'Repository\\',
            'Repository'
        );

        $tableName = $this->doctrineHelper->getPotentialTableName($entityClassDetails->getFullName());

        $entityPath = $this->generator->generateClass(
            $entityClassDetails->getFullName(),
            'doctrine/Entity.tpl.php',
            [
                'repository_full_class_name' => $repoClassDetails->getFullName(),
                'api_resource' => $apiResource,
                'should_escape_table_name' => $this->doctrineHelper->isKeyword($tableName),
                'table_name' => $tableName,
            ]
        );

        $this->generateRepositoryClass(
            $repoClassDetails->getFullName(),
            $entityClassDetails->getFullName(),
            $withPasswordUpgrade)
        ;

        return $entityPath;
    }

    public function generateRepositoryClass(string $repositoryClass, string $entityClass, bool $withPasswordUpgrade)
    {
        $shortEntityClass = Str::getShortClassName($entityClass);
        $entityAlias = strtolower($shortEntityClass[0]);
        $this->generator->generateClass(
            $repositoryClass,
            'doctrine/Repository.tpl.php',
            [
                'entity_full_class_name' => $entityClass,
                'entity_class_name' => $shortEntityClass,
                'entity_alias' => $entityAlias,
                'with_password_upgrade' => $withPasswordUpgrade,
                'doctrine_registry_class' => $this->managerRegistryClassName,
            ]
        );
    }

    /**
     * Called by a compiler pass to inject the non-legacy value if available.
     */
    public function setMangerRegistryClassName(string $managerRegistryClassName)
    {
        $this->managerRegistryClassName = $managerRegistryClassName;
    }
}
