<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Util\ClassSource\Model;

use Doctrine\ORM\Mapping\FieldMapping;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class ClassProperty
{
    public function __construct(
        public string $propertyName,
        public string $type,
        public array $comments = [],
        public ?int $length = null,
        public ?bool $id = null,
        public ?bool $nullable = null,
        public array $options = [],
        public ?int $precision = null,
        public ?int $scale = null,
        public bool $needsTypeHint = true,
        public bool $unique = false,
        public ?string $enumType = null,
    ) {
    }

    public function getAttributes(): array
    {
        $attributes = [];

        if ($this->needsTypeHint) {
            $attributes['type'] = $this->type;
        }

        if (!empty($this->options)) {
            $attributes['options'] = $this->options;
        }

        if ($this->unique) {
            $attributes['unique'] = true;
        }

        if ($this->enumType) {
            $attributes['enumType'] = $this->enumType;
        }

        foreach (['length', 'id', 'nullable', 'precision', 'scale'] as $property) {
            if (null !== $this->$property) {
                $attributes[$property] = $this->$property;
            }
        }

        return $attributes;
    }

    public static function createFromObject(FieldMapping|array $data): self
    {
        if ($data instanceof FieldMapping) {
            return new self(
                propertyName: $data->fieldName,
                type: $data->type,
                length: $data->length,
                id: $data->id ?? false,
                nullable: $data->nullable ?? false,
                options: $data->options ?? [],
                precision: $data->precision,
                scale: $data->scale,
                unique: $data->unique ?? false,
                enumType: $data->enumType,
            );
        }

        /* @legacy Remove when ORM 2.x is no longer supported. */
        if (empty($data['fieldName']) || empty($data['type'])) {
            throw new RuntimeCommandException('Cannot create property model - "fieldName" & "type" are required.');
        }

        return new self(
            propertyName: $data['fieldName'],
            type: $data['type'],
            comments: $data['comments'] ?? [],
            length: $data['length'] ?? null,
            id: $data['id'] ?? false,
            nullable: $data['nullable'] ?? false,
            options: $data['options'] ?? [],
            precision: $data['precision'] ?? null,
            scale: $data['scale'] ?? null,
            unique: $data['unique'] ?? false,
            enumType: $data['enumType'] ?? null,
        );
    }
}
