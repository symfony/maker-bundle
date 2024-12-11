<?php

namespace App\Tests;

use App\GeneratedServiceDecorator;
use App\Service\BarInterface;
use App\Service\FooInterface;
use App\Service\MultipleImpService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratedDecoratorTest extends KernelTestCase
{
    public function testGeneratedDecorator()
    {
        $container = self::getContainer();

        /** @var MultipleImpService $service */
        $service = $container->get(MultipleImpService::class);

        $this->assertInstanceOf(GeneratedServiceDecorator::class, $service);
        $this->assertInstanceOf(FooInterface::class, $service);
        $this->assertInstanceOf(BarInterface::class, $service);
        $this->assertNotInstanceOf(MultipleImpService::class, $service);

        $this->assertSame('THE_FOO_VALUE', $service->getTheValue());
    }
}
