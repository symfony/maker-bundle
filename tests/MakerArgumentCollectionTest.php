<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\MakerArgument;
use Symfony\Bundle\MakerBundle\MakerArgumentCollection;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
final class MakerArgumentCollectionTest extends TestCase
{
    public function testCreatesAndAddsNewArgumentToCollection(): void
    {
        $collection = new MakerArgumentCollection();
        $collection->createArgument('test-arg', 'some-value', false);

        self::assertCount(1, $collection);
        $result = $collection->getArgument('test-arg');

        self::assertSame('some-value', $result->getValue());
        self::assertFalse($result->isRequired());
        self::assertFalse($result->isEmpty());
    }

    public function testAddsArgumentToCollection(): void
    {
        $expected = new MakerArgument('test-arg');

        $collection = new MakerArgumentCollection();
        $collection->addArgument($expected);

        self::assertSame($expected, $collection->getArgument('test-arg'));
    }

    public function methodNameDataProvider(): \Generator
    {
        yield ['getArgument'];
    }

    /**
     * @dataProvider methodNameDataProvider
     */
    public function testThrowsExceptionIfArgumentDoesntExist(string $methodName): void
    {
        $this->markTestIncomplete('Figure out which exception to throw...');
        //@TODO write test
    }

    /**
     * @depends testCreatesAndAddsNewArgumentToCollection
     */
    public function testRemovesArgumentFromCollection(): void
    {
        $collection = new MakerArgumentCollection();
        $collection->createArgument('test-arg', 'value');
        $collection->removeArgument('test-arg');

        self::assertCount(0, $collection);
    }

    /**
     * @depends testAddsArgumentToCollection
     */
    public function testReplacesArgument(): void
    {
        $collection = new MakerArgumentCollection();

        $collection->addArgument(new MakerArgument('old-arg'));
        $collection->replaceArgument($expected = new MakerArgument('old-arg', 'test'));

        self::assertSame($expected, $collection->getArgument('old-arg'));
    }

    /**
     * @depends testCreatesAndAddsNewArgumentToCollection
     */
    public function testGetsArgumentsValue(): void
    {
        $collection = new MakerArgumentCollection();
        $collection->createArgument('test-arg', 'value');

        self::assertSame('value', $collection->getArgumentValue('test-arg'));
    }

    /**
     * @depends testCreatesAndAddsNewArgumentToCollection
     * @depends testGetsArgumentsValue
     */
    public function testSetsArgumentsValue(): void
    {
        $collection = new MakerArgumentCollection();
        $collection->createArgument('test-arg', 'old-value');
        $collection->setArgumentValue('test-arg', 'new-value');

        self::assertSame($collection->getArgumentValue('test-arg'), 'new-value');
    }
}
