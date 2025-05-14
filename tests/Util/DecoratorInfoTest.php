<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\OtherServiceInterface;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\ServiceA;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\ServiceB;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\ServiceC;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\ServiceD;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\ServiceE;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\ServiceF;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\ServiceInterface;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\ServiceZ;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\Sub\ServiceA as SubServiceA;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\Sub\ServiceB as SubServiceB;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\Sub\ServiceD as SubServiceD;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassData;
use Symfony\Bundle\MakerBundle\Util\DecoratorInfo;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

class DecoratorInfoTest extends TestCase
{
    public function testInvalid()
    {
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('Cannot decorate "Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\ServiceZ", its class does not have any interface, parent class and its final.');
        new DecoratorInfo('FooBar', 'foo.bar', ServiceZ::class);
    }

    /** @dataProvider publicMethodsProvider */
    public function testGetPublicMethods(string $decoratedClassOrInterface, array $expected)
    {
        $decoratorInfo = new DecoratorInfo('FooBar', 'foo.bar', $decoratedClassOrInterface);

        $this->assertSame($expected, array_keys($decoratorInfo->getPublicMethods()));
    }

    public function publicMethodsProvider(): \Generator
    {
        yield [ServiceInterface::class, ['getName', 'getDefault']];
        yield [ServiceA::class, ['getName', 'getDefault']];
        yield [ServiceB::class, ['getName', 'getFoo', 'getStaticValue', 'getDefault']];
        yield [ServiceC::class, ['getFoo']];
        yield [ServiceD::class, ['getName', 'getDefault']];
        yield [ServiceE::class, ['getName', 'getDefault']];
        yield [ServiceF::class, ['getName', 'getDefault']];
    }

    /** @dataProvider classDataProvider */
    public function testGetClassData(string $decoratedId, string $decoratedClassOrInterface, ClassData $expected)
    {
        $decoratorInfo = new DecoratorInfo('FooBar', $decoratedId, $decoratedClassOrInterface);

        $this->assertEquals($expected, $decoratorInfo->getClassData());
    }

    public function classDataProvider(): \Generator
    {
        yield [
            'foo.bar',
            ServiceInterface::class,
            ClassData::create(
                class: 'FooBar',
                useStatements: [
                    AsDecorator::class,
                    AutowireDecorated::class,
                ],
                implements: [ServiceInterface::class],
            ),
        ];

        yield [
            'foo.bar',
            ServiceA::class,
            ClassData::create(
                class: 'FooBar',
                useStatements: [
                    AsDecorator::class,
                    AutowireDecorated::class,
                ],
                implements: [ServiceInterface::class],
            ),
        ];

        yield [
            'foo.bar',
            ServiceB::class,
            ClassData::create(
                class: 'FooBar',
                extendsClass: ServiceB::class,
                useStatements: [
                    AsDecorator::class,
                    AutowireDecorated::class,
                    ServiceB::class,
                    ServiceInterface::class,
                ],
            ),
        ];

        yield [
            'foo.bar',
            ServiceC::class,
            ClassData::create(
                class: 'FooBar',
                extendsClass: ServiceC::class,
                useStatements: [
                    AsDecorator::class,
                    AutowireDecorated::class,
                ],
            ),
        ];

        yield [
            'foo.bar',
            ServiceD::class,
            ClassData::create(
                class: 'FooBar',
                useStatements: [
                    AsDecorator::class,
                    AutowireDecorated::class,
                ],
                implements: [ServiceInterface::class],
            ),
        ];

        yield [
            'foo.bar',
            ServiceE::class,
            ClassData::create(
                class: 'FooBar',
                useStatements: [
                    AsDecorator::class,
                    AutowireDecorated::class,
                ],
                implements: [ServiceInterface::class],
            ),
        ];

        yield [
            'foo.bar',
            ServiceF::class,
            ClassData::create(
                class: 'FooBar',
                useStatements: [
                    AsDecorator::class,
                    AutowireDecorated::class,
                ],
                implements: [
                    ServiceInterface::class,
                    OtherServiceInterface::class,
                ],
            ),
        ];

        yield [
            ServiceInterface::class,
            ServiceF::class,
            ClassData::create(
                class: 'FooBar',
                useStatements: [
                    AsDecorator::class,
                    AutowireDecorated::class,
                ],
                implements: [
                    ServiceInterface::class,
                    OtherServiceInterface::class,
                ],
            ),
        ];
    }

    /** @dataProvider decorateAttributeDeclarationProvider */
    public function testGetDecorateAttributeDeclaration(string $serviceId, string $decoratedClassOrInterface, ?int $priority, ?int $onInvalid, string $expected, bool $idAsClassOrInterface)
    {
        $decoratorInfo = new DecoratorInfo('FooBar', $serviceId, $decoratedClassOrInterface, $priority, $onInvalid);

        $this->assertSame($expected, $decoratorInfo->getDecorateAttributeDeclaration());

        if ($idAsClassOrInterface) {
            $this->assertTrue($decoratorInfo->getClassData()->hasUseStatement($serviceId));
        }
    }

    public function decorateAttributeDeclarationProvider(): \Generator
    {
        yield ['foo.bar', ServiceInterface::class, null, null, '#[AsDecorator(decorates: \'foo.bar\')]', false];
        yield [ServiceInterface::class, ServiceInterface::class, null, null, '#[AsDecorator(decorates: ServiceInterface::class)]', true];
        yield ['foo.bar', ServiceInterface::class, 50, null, '#[AsDecorator(decorates: \'foo.bar\', priority: 50)]', false];
        yield ['foo.bar', ServiceInterface::class, null, 0, '#[AsDecorator(decorates: \'foo.bar\', onInvalid: ContainerInterface::RUNTIME_EXCEPTION_ON_INVALID_REFERENCE)]', false];
        yield ['foo.bar', ServiceInterface::class, 50, 0, '#[AsDecorator(decorates: \'foo.bar\', priority: 50, onInvalid: ContainerInterface::RUNTIME_EXCEPTION_ON_INVALID_REFERENCE)]', false];
    }

    public function testInvalidOnInvalid()
    {
        $this->expectException(RuntimeCommandException::class);
        new DecoratorInfo('FooBar', 'foo.bar', ServiceInterface::class, null, -1);
    }

    /** @dataProvider shortNameInnerTypeProvider */
    public function testGetShortNameInnerType(string $decoratedClassOrInterface, string $expected, array $inUseStatements)
    {
        $decoratorInfo = new DecoratorInfo('FooBar', 'foo.bar', $decoratedClassOrInterface);

        $this->assertSame($expected, $decoratorInfo->getShortNameInnerType());

        foreach ($inUseStatements as $inUseStatement) {
            $this->assertTrue($decoratorInfo->getClassData()->hasUseStatement($inUseStatement));
        }
    }

    public function shortNameInnerTypeProvider(): \Generator
    {
        yield [ServiceInterface::class, 'ServiceInterface', [ServiceInterface::class]];
        yield [ServiceA::class, 'ServiceInterface', [ServiceInterface::class]];
        yield [ServiceB::class, 'ServiceB', [ServiceB::class]];
        yield [ServiceC::class, 'ServiceC', [ServiceC::class]];
        yield [ServiceD::class, 'ServiceInterface', [ServiceInterface::class]];
        yield [ServiceE::class, 'ServiceInterface', [ServiceInterface::class]];
        yield [ServiceF::class, 'ServiceInterface&OtherServiceInterface', [ServiceInterface::class, OtherServiceInterface::class]];
    }

    /** @dataProvider aliasOnClassNameProvider */
    public function testAliasOnClassName(string $decoratorClassName, string $decoratedId, string $decoratedClassOrInterface, array $inUseStatements)
    {
        $decoratorInfo = new DecoratorInfo($decoratorClassName, $decoratedId, $decoratedClassOrInterface);

        foreach ($inUseStatements as $class => $alias) {
            $this->assertSame($alias, $decoratorInfo->getClassData()->getUseStatementShortName($class));
        }
    }

    public function aliasOnClassNameProvider(): \Generator
    {
        yield [
            'TheService\\ServiceA',
            SubServiceA::class,
            SubServiceA::class,
            [
                SubServiceA::class => 'ServiceServiceA',
            ],
        ];

        yield [
            'TheService\\ServiceB',
            ServiceB::class,
            ServiceB::class,
            [
                ServiceB::class => 'BaseServiceB',
            ],
        ];

        yield [
            'TheService\\ServiceB',
            SubServiceB::class,
            SubServiceB::class,
            [
                ServiceB::class => 'BaseServiceB',
            ],
        ];

        yield [
            'TheService\\ServiceD',
            SubServiceD::class,
            SubServiceD::class,
            [
                SubServiceD::class => 'BaseServiceD',
            ],
        ];
    }
}
