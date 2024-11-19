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
    private string $expectedBasePath = __DIR__.'/fixtures/expected';

    public function testLoginMethod(): void
    {
        $this->runMethodTest(
            'addLoginMethod',
            \sprintf('%s/%s', $this->expectedBasePath, 'SecurityController_login.php')
        );
    }

    public function testLogoutMethod(): void
    {
        $this->runMethodTest(
            'addLogoutMethod',
            \sprintf('%s/%s', $this->expectedBasePath, 'SecurityController_logout.php')
        );
    }

    public function testLoginAndLogoutMethod(): void
    {
        $builder = new SecurityControllerBuilder();
        $csm = $this->getClassSourceManipulator();

        $builder->addLoginMethod($csm);
        $builder->addLogoutMethod($csm);

        $this->assertStringEqualsFile(
            \sprintf('%s/%s', $this->expectedBasePath, 'SecurityController_login_logout.php'),
            $csm->getSourceCode()
        );
    }

    private function runMethodTest(string $builderMethod, string $expectedFilePath): void
    {
        $builder = new SecurityControllerBuilder();
        $csm = $this->getClassSourceManipulator();

        $builder->$builderMethod($csm);
        $this->assertStringEqualsFile($expectedFilePath, $csm->getSourceCode());
    }

    private function getClassSourceManipulator(): ClassSourceManipulator
    {
        return new ClassSourceManipulator(file_get_contents(__DIR__.'/fixtures/source/SecurityController.php'));
    }
}
