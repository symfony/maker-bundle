<?php

namespace App\Tests;

use App\Message\SendWelcomeEmail;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratedMessageHandlerTest extends KernelTestCase
{
    public function testGeneratedMessage()
    {
        self::bootKernel();
        $messageBus = self::$kernel->getContainer()->get('test.message_bus');

        $messageBus->dispatch(new SendWelcomeEmail());
        $this->assertTrue(true, 'Smoke testing the handler did not explode');
    }
}
