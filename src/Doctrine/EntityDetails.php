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

use Doctrine\Persistence\Mapping\ClassMetadata;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * @author Sadicov Vladimir <sadikoff@gmail.com>
 *
 * @internal
 */
final class EntityDetails
{
    public function __construct(
        private ClassMetadata $metadata,
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

        $fieldsWithTypes = [];
        foreach ($fields as $field) {
            $fieldsWithTypes[$field] = null;
        }

        foreach ($this->metadata->fieldMappings as $fieldName => $fieldMapping) {
            $propType = DoctrineHelper::getPropertyTypeForColumn($fieldMapping['type']);
            if (($propType === '\\'.\DateTimeImmutable::class)
                || ($propType === '\\'.\DateTimeInterface::class)) {
                $fieldsWithTypes[$fieldName] = [
                    'type' => null,
                    'options_code' => "'widget' => 'single_text'",
                ];
            }
        }
        foreach ($this->metadata->associationMappings as $fieldName => $relation) {
            if (\Doctrine\ORM\Mapping\ClassMetadata::ONE_TO_MANY === $relation['type']) {
                continue;
            }
            $fieldsWithTypes[$fieldName] = [
                'type' => EntityType::class,
                'options_code' => \sprintf('\'class\' => %s::class,', $relation['targetEntity']).\PHP_EOL.'\'choice_label\' => \'id\',',
                'extra_use_classes' => [$relation['targetEntity']],
            ];
            if (\Doctrine\ORM\Mapping\ClassMetadata::MANY_TO_MANY === $relation['type']) {
                $fieldsWithTypes[$fieldName]['options_code'] .= "\n'multiple' => true,";
            }
        }

        return $fieldsWithTypes;
    }
}
