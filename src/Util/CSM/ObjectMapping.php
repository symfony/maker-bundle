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

class ObjectMapping
{
    public function __construct(
        public string $type,
        public ?int $length = null,
        public ?string $id = null,
        public ?bool $nullable = null,
        public array $options = [],
        public ?int $precision = null,
        public ?int $scale = null,
        public bool $needsTypeHint = true,
    ) {
    }

    public static function createFromObject(\Doctrine\ORM\Mapping\FieldMapping $mapping): self
    {
        return new self(
            type: $mapping->type,
            id: $mapping->id,
            nullable: $mapping->nullable,
        );
    }
}
