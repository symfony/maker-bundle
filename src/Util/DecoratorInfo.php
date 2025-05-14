<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Util;

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassData;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassMethod;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\MethodArgument;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
final class DecoratorInfo
{
    private readonly ClassData $classData;

    private readonly array $methods;

    private readonly array $decoratedClassOrInterfaces;

    private readonly string $decoratedIdDeclaration;

    private readonly ?string $onInvalid;

    /**
     * @param class-string $decoratorClassName
     * @param class-string $decoratedClassOrInterface
     */
    public function __construct(
        private readonly string $decoratorClassName,
        string $decoratedId,
        string $decoratedClassOrInterface,
        private readonly ?int $priority = null,
        ?int $onInvalid = null,
    ) {
        $decoratedTypeRef = new \ReflectionClass($decoratedClassOrInterface);

        // Try implements
        $interfaces = match (true) {
            interface_exists($decoratedClassOrInterface) => [$decoratedClassOrInterface],
            self::isClassEquivalentToItsInterfaces($decoratedTypeRef) => array_values(class_implements($decoratedClassOrInterface)),
            default => null,
        };

        // Try extends if cannot implements.
        $extends = (null === $interfaces) ? match (true) {
            self::isClassEquivalentToItsParentClass($decoratedTypeRef) => get_parent_class($decoratedClassOrInterface),
            !$decoratedTypeRef->isFinal() => $decoratedClassOrInterface,
            default => throw new RuntimeCommandException(\sprintf('Cannot decorate "%s", its class does not have any interface, parent class and its final.', $decoratedClassOrInterface)),
        } : null;

        $this->classData = ClassData::create(
            class: $this->decoratorClassName,
            extendsClass: $extends,
            useStatements: [
                AsDecorator::class,
                AutowireDecorated::class,
            ],
            implements: $interfaces,
        );

        // Use interfaces or extends as decorated type
        $this->decoratedClassOrInterfaces = $interfaces ?? [$extends];

        // Handle decorated service's id.
        if (class_exists($decoratedId) || interface_exists($decoratedId)) {
            if (!$this->classData->hasUseStatement($decoratedId)) {
                $this->classData->addUseStatement($decoratedId, 'Service');
            }

            $this->decoratedIdDeclaration = \sprintf('%s::class', $this->classData->getUseStatementShortName($decoratedId));
        } else {
            $this->decoratedIdDeclaration = \sprintf('\'%s\'', $decoratedId);
        }

        if (null === $onInvalid) {
            $this->onInvalid = null;
        } else {
            $this->classData->addUseStatement(ContainerInterface::class);

            $ok = false;
            $allowedValues = [];
            $ref = new \ReflectionClass(ContainerInterface::class);
            foreach ($ref->getConstants(\ReflectionClassConstant::IS_PUBLIC) as $name => $value) {
                if ($onInvalid === $value) {
                    $this->onInvalid = \sprintf('ContainerInterface::%s', $name);
                    $ok = true;
                    break;
                }
                $allowedValues[] = $value;
            }

            if (!$ok) {
                throw new RuntimeCommandException(\sprintf('Invalid "onInvalid" value "%d", it must be one of %s.', $onInvalid, implode(', ', $allowedValues)));
            }
        }

        // Trigger methods parsing to register methods arguments type in use statement.
        $this->methods = $this->doGetPublicMethods();
    }

    /**
     * @return array<ClassMethod>
     */
    public function getPublicMethods(): array
    {
        return $this->methods;
    }

    public function getClassData(): ClassData
    {
        return $this->classData;
    }

    public function getShortNameInnerType(): string
    {
        return implode('&', array_map($this->classData->getUseStatementShortName(...), $this->decoratedClassOrInterfaces));
    }

    public function getDecorateAttributeDeclaration(): string
    {
        return \sprintf(
            '#[AsDecorator(decorates: %s%s%s)]',
            $this->decoratedIdDeclaration,
            null !== $this->priority ? \sprintf(', priority: %d', $this->priority) : '',
            null !== $this->onInvalid ? \sprintf(', onInvalid: %s', $this->onInvalid) : '',
        );
    }

    /**
     * @return array<class-string, ClassMethod>
     */
    private function doGetPublicMethods(): array
    {
        $methods = [];
        foreach ($this->decoratedClassOrInterfaces as $classOrInterface) {
            $ref = new \ReflectionClass($classOrInterface);

            foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isFinal() || \array_key_exists($method->getName(), $methods) || '__construct' === $method->getName()) {
                    continue;
                }

                $methods[$method->getName()] = new ClassMethod(
                    $method->getName(),
                    [...$this->doParseArguments($method)],
                    $this->parseType($method->getReturnType()),
                    $method->isStatic(),
                );
            }
        }

        return $methods;
    }

    /**
     * @return iterable<MethodArgument>
     */
    private function doParseArguments(\ReflectionMethod $method): iterable
    {
        foreach ($method->getParameters() as $parameter) {
            $default = null;
            if ($parameter->isOptional()) {
                if ($parameter->isDefaultValueConstant()) {
                    $default = $parameter->getDefaultValueConstantName();
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $defaultValue = $parameter->getDefaultValue();

                    if (\is_string($defaultValue)) {
                        $default = '\''.str_replace('\'', '\\\'', $defaultValue).'\'';
                    } elseif (\is_scalar($defaultValue)) {
                        $default = $defaultValue;
                    } elseif (\is_array($defaultValue)) {
                        $default = '[]';
                    } elseif (null === $defaultValue) {
                        $default = 'null';
                    }
                }
            }

            yield new MethodArgument(
                $parameter->getName(),
                $this->parseType($parameter->getType()),
                $default,
            );
        }
    }

    private function parseType(?\ReflectionType $type): ?string
    {
        if (null === $type) {
            return null;
        }

        if ($type instanceof \ReflectionNamedType) {
            if (!$type->isBuiltin()) {
                $this->classData->addUseStatement($type->getName(), 'Arg');

                return $this->classData->getUseStatementShortName($type->getName());
            }

            return $type->getName();
        }

        if ($type instanceof \ReflectionUnionType) {
            return implode('|', array_map($this->parseType(...), $type->getTypes()));
        }

        if ($type instanceof \ReflectionIntersectionType) {
            return implode('&', array_map($this->parseType(...), $type->getTypes()));
        }

        throw new RuntimeCommandException('Should never be reach.');
    }

    private static function isClassEquivalentToItsInterfaces(\ReflectionClass $classRef): bool
    {
        if (empty($interfaceRefs = $classRef->getInterfaces())) {
            return false;
        }

        $interfaceMethods = [];
        foreach ($interfaceRefs as $ref) {
            $methodRefs = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach ($methodRefs as $methodRef) {
                $interfaceMethods[] = $methodRef->getName();
            }
        }

        $interfaceMethods = array_unique($interfaceMethods);

        $classMethodsCount = \count($classRef->getMethods(\ReflectionMethod::IS_PUBLIC));
        if ($classRef->hasMethod('__construct')) {
            --$classMethodsCount;
        }

        return \count($interfaceMethods) === $classMethodsCount;
    }

    private static function isClassEquivalentToItsParentClass(\ReflectionClass $classRef): bool
    {
        if (false === $parentClassRef = $classRef->getParentClass()) {
            return false;
        }

        $classMethodsCount = \count($classRef->getMethods(\ReflectionMethod::IS_PUBLIC));
        if ($classRef->hasMethod('__construct')) {
            --$classMethodsCount;
        }

        $parentMethodsCount = \count($parentClassRef->getMethods(\ReflectionMethod::IS_PUBLIC));
        if ($parentClassRef->hasMethod('__construct')) {
            --$parentMethodsCount;
        }

        return $classMethodsCount === $parentMethodsCount;
    }
}
