<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Util\CSM;

class ClassPropertyModel
{
    public function __construct(
        public string $propertyName,
        public string $type,
        public array $comments = [],
        public ?int $length = null,
        public ?string $id = null,
        public ?bool $nullable = null,
        public array $options = [],
        public ?int $precision = null,
        public ?int $scale = null,
        public bool $needsTypeHint = true,
        public bool $unique = false,
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

        foreach (['length', 'id', 'nullable', 'precision', 'scale'] as $property) {
            if (null !== $this->$property) {
                $attributes[$property] = $this->$property;
            }
        }

        return $attributes;
    }

//    public static function createFromObject(\Doctrine\ORM\Mapping\FieldMapping $mapping): self
//    {
////        return new self(
////            type: $mapping->type,
////            id: $mapping->id,
////            nullable: $mapping->nullable,
////        );
//    }
}
