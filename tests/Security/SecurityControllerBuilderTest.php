<?php

namespace Symfony\Bundle\MakerBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Security\SecurityControllerBuilder;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;

class SecurityControllerBuilderTest extends TestCase
{
    public function testAddLoginMethod()
    {
        $source         = file_get_contents(__DIR__.'/fixtures/source/SecurityController.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/expected/SecurityController_login.php');

        $manipulator = new ClassSourceManipulator($source);

        $securityControllerBuilder = new SecurityControllerBuilder();
        $securityControllerBuilder->addLoginMethod($manipulator);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }
}