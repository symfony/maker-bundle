<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineEventRegistry;

class DoctrineEventRegistryTest extends TestCase
{
    private static ?DoctrineEventRegistry $doctrineEventRegistry = null;

    public static function setUpBeforeClass(): void
    {
        self::$doctrineEventRegistry = new DoctrineEventRegistry();
    }

    public static function tearDownAfterClass(): void
    {
        self::$doctrineEventRegistry = null;
    }

    /**
     * @dataProvider provideIsLifecycleEvent
     */
    #[DataProvider('provideIsLifecycleEvent')]
    public function testIsLifecycleEvent(string $event, bool $expected)
    {
        self::assertSame($expected, self::$doctrineEventRegistry->isLifecycleEvent($event));
    }

    public static function provideIsLifecycleEvent(): \Generator
    {
        yield ['prePersist', true];
        yield ['preUpdate', true];
        yield ['preFlush', true];
        yield ['loadClassMetadata', false];
        yield ['onFlush', false];
        yield ['postFlush', false];
    }

    /**
     * @dataProvider provideGetEventClassName
     */
    #[DataProvider('provideGetEventClassName')]
    public function testGetEventClassName(string $event, ?string $expected)
    {
        self::assertSame($expected, self::$doctrineEventRegistry->getEventClassName($event));
    }

    public static function provideGetEventClassName(): \Generator
    {
        yield ['preUpdate', PreUpdateEventArgs::class];
        yield ['preFlush', PreFlushEventArgs::class];
        yield ['onFlush', OnFlushEventArgs::class];
        yield ['postGenerateSchemaTable', GenerateSchemaTableEventArgs::class];
        yield ['foo', null];
        yield ['bar', null];
    }

    /**
     * @dataProvider provideGetEventConstantClassName
     */
    #[DataProvider('provideGetEventConstantClassName')]
    public function testGetEventConstantClassName(string $event, ?string $expected)
    {
        self::assertSame($expected, self::$doctrineEventRegistry->getEventConstantClassName($event));
    }

    public static function provideGetEventConstantClassName(): \Generator
    {
        yield ['preUpdate', Events::class];
        yield ['preFlush', Events::class];
        yield ['onFlush', Events::class];
        yield ['postGenerateSchemaTable', ToolEvents::class];
        yield ['foo', null];
        yield ['bar', null];
    }
}
