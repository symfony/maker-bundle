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
use Symfony\Bundle\MakerBundle\EventRegistry;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EventRegistryTest extends TestCase
{
    public function testGetEventClassNameReturnsType()
    {
        $eventObj = new DummyEvent();
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $listenersMap = [
            'someFunctionToSkip',
            [$eventObj, 'methodNoArg'],
            [$eventObj, 'methodNoType'],
            [$eventObj, 'methodObjectType'],
            [$eventObj, 'methodWithType'],
        ];

        // less than PHP 7.2, unset object type-hint example
        // otherwise, it looks like a class in this namespace
        if (\PHP_VERSION_ID < 70200) {
            unset($listenersMap[3]);
        }

        $dispatcher->expects($this->once())
            ->method('getListeners')
            ->with('foo.bar')
            ->willReturn($listenersMap);

        $registry = new EventRegistry($dispatcher);
        $this->assertSame(GetResponseForExceptionEvent::class, $registry->getEventClassName('foo.bar'));
    }

    public function testGetEventClassNameReturnsNoType()
    {
        $eventObj = new DummyEvent();
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('getListeners')
            ->with('foo.bar')
            ->willReturn([
                'someFunctionToSkip',
                [$eventObj, 'methodNoArg'],
                [$eventObj, 'methodNoType'],
            ]);

        $registry = new EventRegistry($dispatcher);
        $this->assertNull($registry->getEventClassName('foo.bar'));
    }

    public function testGetOldEventClassNameFromStandardList()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())
            ->method('getListeners');

        $registry = new EventRegistry($dispatcher);
        $this->assertSame(ConsoleCommandEvent::class, $registry->getEventClassName('console.command'));
    }

    public function testGetNewEventClassNameFromStandardList()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())
                   ->method('getListeners');

        $registry = new EventRegistry($dispatcher);
        $this->assertSame(ExceptionEvent::class, $registry->getEventClassName(KernelEvents::EXCEPTION));
    }

    public function testGetEventClassNameGivenEventAsClass()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $registry = new EventRegistry($dispatcher);
        $this->assertSame(ControllerEvent::class, $registry->getEventClassName(ControllerEvent::class));
    }
}

class DummyEvent
{
    public function methodNoArg()
    {
    }

    public function methodNoType($event)
    {
    }

    public function methodObjectType(object $event)
    {
    }

    public function methodWithType(GetResponseForExceptionEvent $event)
    {
    }
}
