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
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class ClassData
{
    private function __construct(
        private string $className,
        private string $namespace,
        public readonly ?string $extends,
        public readonly bool $isEntity,
        private UseStatementGenerator $useStatementGenerator,
        private bool $isFinal = true,
        private string $rootNamespace = 'App',
        private ?string $classSuffix = null,
    ) {
        if (str_starts_with(haystack: $this->namespace, needle: $this->rootNamespace)) {
            $this->namespace = substr_replace(string: $this->namespace, replace: '', offset: 0, length: \strlen($this->rootNamespace) + 1);
        }
    }

    public static function create(string $class, ?string $suffix = null, ?string $extendsClass = null, bool $isEntity = false, array $useStatements = []): self
    {
        $className = Str::getShortClassName($class);

        if (null !== $suffix && !str_ends_with($className, $suffix)) {
            $className = Str::asClassName(\sprintf('%s%s', $className, $suffix));
        }

        $useStatements = new UseStatementGenerator($useStatements);

        if ($extendsClass) {
            $useStatements->addUseStatement($extendsClass);
        }

        return new self(
            className: Str::asClassName($className),
            namespace: Str::getNamespace($class),
            extends: null === $extendsClass ? null : Str::getShortClassName($extendsClass),
            isEntity: $isEntity,
            useStatementGenerator: $useStatements,
            classSuffix: $suffix,
        );
    }

    public function getClassName(bool $relative = false, bool $withoutSuffix = false): string
    {
        if (!$withoutSuffix && !$relative) {
            return $this->className;
        }

        if ($relative) {
            $class = \sprintf('%s\%s', $this->namespace, $this->className);

            $firstNsSeparatorPosition = stripos($class, '\\');
            $class = substr_replace(string: $class, replace: '', offset: 0, length: $firstNsSeparatorPosition + 1);

            if ($withoutSuffix) {
                $class = Str::removeSuffix($class, $this->classSuffix);
            }

            return $class;
        }

        return Str::removeSuffix($this->className, $this->classSuffix);
    }

    public function getNamespace(): string
    {
        if (empty($this->namespace)) {
            return $this->rootNamespace;
        }

        // Namespace is already absolute, don't add the rootNamespace.
        if (str_starts_with($this->namespace, '\\')) {
            return substr_replace($this->namespace, '', 0, 1);
        }

        return \sprintf('%s\%s', $this->rootNamespace, $this->namespace);
    }

    /**
     * Get the full class name.
     *
     * @param bool $withoutRootNamespace Get the full class name without global root namespace. e.g. "App"
     * @param bool $withoutSuffix        Get the full class name without the class suffix. e.g. "MyController" instead of "MyControllerController"
     */
    public function getFullClassName($withoutRootNamespace = false, $withoutSuffix = false): string
    {
        $className = \sprintf('%s\%s', $this->getNamespace(), $withoutSuffix ? Str::removeSuffix($this->className, $this->classSuffix) : $this->className);

        if ($withoutRootNamespace) {
            if (str_starts_with(haystack: $className, needle: $this->rootNamespace)) {
                $className = substr_replace(string: $className, replace: '', offset: 0, length: \strlen($this->rootNamespace) + 1);
            }
        }

        return $className;
    }

    public function setRootNamespace(string $rootNamespace): self
    {
        $this->rootNamespace = $rootNamespace;

        return $this;
    }

    public function getClassDeclaration(): string
    {
        $extendsDeclaration = '';

        if (null !== $this->extends) {
            $extendsDeclaration = \sprintf(' extends %s', $this->extends);
        }

        return \sprintf('%sclass %s%s',
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

    public function addUseStatement(array|string $useStatement): self
    {
        $this->useStatementGenerator->addUseStatement($useStatement);

        return $this;
    }

    public function getUseStatements(): string
    {
        return (string) $this->useStatementGenerator;
    }
}
