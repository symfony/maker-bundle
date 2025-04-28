<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratedTwigComponentTest extends KernelTestCase
{
    public function testController()
    {
        $output = self::getContainer()->get('twig')->createTemplate("<twig:{name} />")->render();

        $this->assertStringContainsString('<div data-controller="live"', $output);
        $this->assertStringContainsString('data-live-name-value="', $output);
        $this->assertStringContainsString('data-live-url-value=', $output);
        $this->assertStringContainsString('<!-- component HTML -->', $output);
    }
}
