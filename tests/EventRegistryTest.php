<?php

namespace Symfony\Bundle\MakerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\EventRegistry;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class EventRegistryTest extends TestCase
{
    public function testGetEventClassNameReturnsType()
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
                [$eventObj, 'methodWithType'],
            ]);

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

    public function testGetEventClassNameFromStandardList()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())
            ->method('getListeners');

        $registry = new EventRegistry($dispatcher);
        $this->assertSame(GetResponseForExceptionEvent::class, $registry->getEventClassName('kernel.exception'));
    }
}

class DummyEvent extends Event
{
    public function methodNoArg()
    {
    }

    public function methodNoType($event)
    {
    }

    public function methodWithType(GetResponseForExceptionEvent $event)
    {
    }
}
