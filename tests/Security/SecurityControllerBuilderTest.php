<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Security\SecurityControllerBuilder;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;

class SecurityControllerBuilderTest extends TestCase
{
    public function testAddLoginMethod()
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/SecurityController.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/expected/SecurityController_login.php');

        $manipulator = new ClassSourceManipulator($source);

        $securityControllerBuilder = new SecurityControllerBuilder();
        $securityControllerBuilder->addLoginMethod($manipulator);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testLogoutMethod()
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/SecurityController.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/expected/SecurityController_logout.php');

        $manipulator = new ClassSourceManipulator($source);

        $securityControllerBuilder = new SecurityControllerBuilder();
        $securityControllerBuilder->addLogoutMethod($manipulator);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testLoginAndLogoutMethod()
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/SecurityController.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/expected/SecurityController_login_logout.php');

        $manipulator = new ClassSourceManipulator($source);

        $securityControllerBuilder = new SecurityControllerBuilder();
        $securityControllerBuilder->addLoginMethod($manipulator);
        $securityControllerBuilder->addLogoutMethod($manipulator);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }
}
