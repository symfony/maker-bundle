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

use Doctrine\Common\Persistence\Mapping\ClassMetadata as LegacyClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * @author Sadicov Vladimir <sadikoff@gmail.com>
 *
 * @internal
 */
final class EntityDetails
{
    public function __construct(
        private ClassMetadata|LegacyClassMetadata $metadata,
    ) {
    }

    public function getRepositoryClass(): ?string
    {
        return $this->metadata->customRepositoryClassName;
    }

    public function getIdentifier()
    {
        return $this->metadata->identifier[0];
    }

    public function getDisplayFields(): array
    {
        return $this->metadata->fieldMappings;
    }

    public function getFormFields(): array
    {
        $fields = (array) $this->metadata->fieldNames;
        // Remove the primary key field if it's not managed manually
        if (!$this->metadata->isIdentifierNatural()) {
            $fields = array_diff($fields, $this->metadata->identifier);
        }
        $fields = array_values($fields);

        if (!empty($this->metadata->embeddedClasses)) {
            foreach (array_keys($this->metadata->embeddedClasses) as $embeddedClassKey) {
                $fields = array_filter($fields, static fn ($v) => !str_starts_with($v, $embeddedClassKey.'.'));
            }
        }

        foreach ($this->metadata->associationMappings as $fieldName => $relation) {
            if (\Doctrine\ORM\Mapping\ClassMetadata::ONE_TO_MANY !== $relation['type']) {
                $fields[] = $fieldName;
            }
        }

        $fieldsWithTypes = [];
        foreach ($fields as $field) {
            $fieldsWithTypes[$field] = null;
        }

        return $fieldsWithTypes;
    }
}
