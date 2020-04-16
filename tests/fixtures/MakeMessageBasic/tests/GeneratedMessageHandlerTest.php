<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
