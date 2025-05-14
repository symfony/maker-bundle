<?php

namespace App\Tests;

use App\GeneratedServiceDecorator;
use App\Service\ForExtendService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratedDecoratorTest extends KernelTestCase
{
    public function testGeneratedDecorator()
    {
        $container = self::getContainer();

        /** @var ForExtendService $service */
        $service = $container->get(ForExtendService::class);

        $this->assertInstanceOf(GeneratedServiceDecorator::class, $service);
        $this->assertInstanceOf(ForExtendService::class, $service);
    }
}
