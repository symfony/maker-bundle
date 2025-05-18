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
     * @testWith ["prePersist", true]
     *           ["preUpdate", true]
     *           ["preFlush", true]
     *           ["loadClassMetadata", false]
     *           ["onFlush", false]
     *           ["postFlush", false]
     */
    public function testIsLifecycleEvent(string $event, bool $expected)
    {
        self::assertSame($expected, self::$doctrineEventRegistry->isLifecycleEvent($event));
    }

    /**
     * @testWith ["preUpdate", "Doctrine\\ORM\\Event\\PreUpdateEventArgs"]
     *           ["preFlush", "Doctrine\\ORM\\Event\\PreFlushEventArgs"]
     *           ["onFlush", "Doctrine\\ORM\\Event\\OnFlushEventArgs"]
     *           ["postGenerateSchemaTable", "Doctrine\\ORM\\Tools\\Event\\GenerateSchemaTableEventArgs"]
     *           ["foo", null]
     *           ["bar", null]
     */
    public function testGetEventClassName(string $event, ?string $expected)
    {
        self::assertSame($expected, self::$doctrineEventRegistry->getEventClassName($event));
    }

    /**
     * @testWith ["preUpdate", "Doctrine\\ORM\\Events"]
     *           ["preFlush", "Doctrine\\ORM\\Events"]
     *           ["onFlush", "Doctrine\\ORM\\Events"]
     *           ["postGenerateSchemaTable", "Doctrine\\ORM\\Tools\\ToolEvents"]
     *           ["foo", null]
     *           ["bar", null]
     */
    public function testGetEventConstantClassName(string $event, ?string $expected)
    {
        self::assertSame($expected, self::$doctrineEventRegistry->getEventConstantClassName($event));
    }
}
