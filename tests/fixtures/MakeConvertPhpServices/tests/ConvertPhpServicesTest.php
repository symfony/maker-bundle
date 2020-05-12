<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConvertPhpServicesTest extends WebTestCase
{
    public function testIfServicesPhpFileExists ()
    {
        $this->assertTrue(file_exists('config/services.php'));
        $this->assertFalse(file_exists('config/services.yaml'));
    }
}
