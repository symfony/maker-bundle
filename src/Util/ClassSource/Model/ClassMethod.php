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

/**
 * @author Benjamin Georgeault <git@wedgesama.fr>
 *
 * @internal
 */
final class ClassMethod
{
    /**
     * @param MethodArgument[] $arguments
     */
    public function __construct(
        private readonly string $name,
        private readonly array $arguments = [],
        private readonly ?string $returnType = null,
        private readonly bool $isStatic = false,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isReturnVoid(): bool
    {
        return 'void' === $this->returnType;
    }

    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    public function getDeclaration(): string
    {
        return \sprintf('public %sfunction %s(%s)%s',
            $this->isStatic ? 'static ' : '',
            $this->name,
            implode(', ', array_map(fn (MethodArgument $arg) => $arg->getDeclaration(), $this->arguments)),
            $this->returnType ? ': '.$this->returnType : '',
        );
    }

    public function getArgumentsUse(): string
    {
        return implode(', ', array_map(fn (MethodArgument $arg) => $arg->getVariable(), $this->arguments));
    }
}
