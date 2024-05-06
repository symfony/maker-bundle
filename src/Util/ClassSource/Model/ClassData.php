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

use Symfony\Bundle\MakerBundle\Str;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class ClassData
{
    private function __construct(
        public readonly string $className,
        public readonly string $namespace,
        public readonly string $fullClassName,
        public readonly ?string $extends,
        public readonly bool $isEntity,
        private bool $isFinal = true,
    ) {
    }

    public static function create(string $class, ?string $extendsClass = null, bool $isEntity = false): self
    {
        return new self(
            className: Str::getShortClassName($class),
            namespace: Str::getNamespace($class),
            fullClassName: $class,
            extends: null === $extendsClass ? null : Str::getShortClassName($extendsClass),
            isEntity: $isEntity,
        );
    }

    public function getClassDeclaration(): string
    {
        $extendsDeclaration = '';

        if (null !== $this->extends) {
            $extendsDeclaration = sprintf(' extends %s', $this->extends);
        }

        return sprintf('%sclass %s%s',
            $this->isFinal ? 'final ' : '',
            $this->className,
            $extendsDeclaration,
        );
    }

    public function setIsFinal(bool $isFinal): self
    {
        $this->isFinal = $isFinal;

        return $this;
    }
}
