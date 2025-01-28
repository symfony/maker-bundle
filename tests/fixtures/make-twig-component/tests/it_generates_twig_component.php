<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratedTwigComponentTest extends KernelTestCase
{
    public function testController()
    {
        $output = self::getContainer()->get('twig')->createTemplate("<twig:{name} />")->render();

        $this->assertSame("<div>\n    <!-- component HTML -->\n</div>\n", $output);
    }
}
