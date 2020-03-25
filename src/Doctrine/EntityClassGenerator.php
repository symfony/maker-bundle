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

use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

/**
 * @internal
 */
final class EntityClassGenerator
{
    private $generator;

    public function __construct(Generator $generator)
    {
        $this->generator = $generator;
    }

    public function generateEntityClass(ClassNameDetails $entityClassDetails, bool $apiResource, bool $withPasswordUpgrade = false): string
    {
        $repoClassDetails = $this->generator->createClassNameDetails(
            $entityClassDetails->getRelativeName(),
            'Repository\\',
            'Repository'
        );

        $entityPath = $this->generator->generateClass(
            $entityClassDetails->getFullName(),
            $this->generator->getTemplateNameEntity(),
            [
                'repository_full_class_name' => $repoClassDetails->getFullName(),
                'api_resource' => $apiResource,
            ]
        );

        $entityAlias = strtolower($entityClassDetails->getShortName()[0]);
        $this->generator->generateClass(
            $repoClassDetails->getFullName(),
            $this->generator->getTemplateNameRepository(),
            [
                'entity_full_class_name' => $entityClassDetails->getFullName(),
                'entity_class_name' => $entityClassDetails->getShortName(),
                'entity_alias' => $entityAlias,
                'with_password_upgrade' => $withPasswordUpgrade,
            ]
        );

        return $entityPath;
    }
}
