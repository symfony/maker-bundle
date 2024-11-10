<?php

namespace App\Tests;

use App\GeneratedServiceDecorator;
use App\Service\FooInterface;
use App\Service\FooService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratedDecoratorTest extends KernelTestCase
{
    public function testGeneratedDecorator()
    {
        $container = self::getContainer();

        /** @var FooService $service */
        $service = $container->get(FooService::class);

        $this->assertInstanceOf(GeneratedServiceDecorator::class, $service);
        $this->assertInstanceOf(FooInterface::class, $service);
        $this->assertNotInstanceOf(FooService::class, $service);

        $this->assertSame('THE_FOO_VALUE', $service->getTheValue());
    }
}
