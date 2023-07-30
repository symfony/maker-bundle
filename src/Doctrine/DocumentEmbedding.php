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

/**
 * @internal
 */
final class DocumentEmbedding
{
    public const EMBED_ONE = 'EmbedOne';
    public const EMBED_MANY = 'EmbedMany';

    private $owningProperty;
    private bool $isNullable = false;

    public function __construct(
        private string $type,
        private string $owningClass,
        private string $targetClass,
    ) {
        if (!\in_array($type, self::getValidEmbeddingTypes())) {
            throw new \Exception(sprintf('Invalid embedding type "%s"', $type));
        }
    }

    public function setOwningProperty(string $owningProperty): void
    {
        $this->owningProperty = $owningProperty;
    }

    public function setIsNullable(bool $isNullable): void
    {
        $this->isNullable = $isNullable;
    }

    public static function getValidEmbeddingTypes(): array
    {
        return [
            self::EMBED_ONE,
            self::EMBED_MANY,
        ];
    }

    public function getDocumentEmbedding(): EmbedOne|EmbedMany
    {
        return match ($this->getType()) {
            self::EMBED_ONE => (new EmbedOne(
                propertyName: $this->owningProperty,
                targetClassName: $this->targetClass,
                isNullable: $this->isNullable,
            )),
            self::EMBED_MANY => (new EmbedMany(
                propertyName: $this->owningProperty,
                targetClassName: $this->targetClass,
                isNullable: $this->isNullable,
            )),

            default => throw new \InvalidArgumentException('Invalid type'),
        };
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getOwningClass(): string
    {
        return $this->owningClass;
    }

    public function getTargetClass(): string
    {
        return $this->targetClass;
    }

    public function getOwningProperty(): string
    {
        return $this->owningProperty;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }
}
