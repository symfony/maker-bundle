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
use Symfony\Bundle\MakerBundle\Util\PhpCompatUtil;

class SecurityControllerBuilderTest extends TestCase
{
    private $expectedBasePath = __DIR__.'/fixtures/expected';

    public function testLoginMethod(): void
    {
        /* @legacy Can be dropped when PHP 7.x support is dropped in MakerBundle */
        $this->runMethodTest(
            'addLoginMethod',
            true,
            sprintf('%s/legacy_add_login_method/%s', $this->expectedBasePath, 'SecurityController_login.php')
        );

        if ((\PHP_VERSION_ID >= 80000)) {
            $this->runMethodTest(
                'addLoginMethod',
                false,
                sprintf('%s/%s', $this->expectedBasePath, 'SecurityController_login.php')
            );
        }
    }

    public function testLogoutMethod(): void
    {
        /* @legacy Can be dropped when PHP 7.x support is dropped in MakerBundle */
        $this->runMethodTest(
            'addLogoutMethod',
            true,
            sprintf('%s/legacy_add_logout_method/%s', $this->expectedBasePath, 'SecurityController_logout.php')
        );

        if ((\PHP_VERSION_ID >= 80000)) {
            $this->runMethodTest(
                'addLogoutMethod',
                false,
                sprintf('%s/%s', $this->expectedBasePath, 'SecurityController_logout.php')
            );
        }
    }

    public function testLoginAndLogoutMethod(): void
    {
        /** @legacy Can be dropped when PHP 7.x support is dropped in MakerBundle */
        $builder = $this->getSecurityControllerBuilder(true);
        $csm = $this->getClassSourceManipulator();

        $builder->addLoginMethod($csm);
        $builder->addLogoutMethod($csm);

        $this->assertStringEqualsFile(
            sprintf('%s/legacy_add_login_logout_method/%s', $this->expectedBasePath, 'SecurityController_login_logout.php'),
            $csm->getSourceCode()
        );

        if ((\PHP_VERSION_ID >= 80000)) {
            $builder = $this->getSecurityControllerBuilder(false);
            $csm = $this->getClassSourceManipulator();

            $builder->addLoginMethod($csm);
            $builder->addLogoutMethod($csm);

            $this->assertStringEqualsFile(
                sprintf('%s/%s', $this->expectedBasePath, 'SecurityController_login_logout.php'),
                $csm->getSourceCode()
            );
        }
    }

    private function runMethodTest(string $builderMethod, bool $isLegacyTest, string $expectedFilePath): void
    {
        $builder = $this->getSecurityControllerBuilder($isLegacyTest);
        $csm = $this->getClassSourceManipulator();

        $builder->$builderMethod($csm);
        $this->assertStringEqualsFile($expectedFilePath, $csm->getSourceCode());
    }

    private function getClassSourceManipulator(): ClassSourceManipulator
    {
        return new ClassSourceManipulator(file_get_contents(__DIR__.'/fixtures/source/SecurityController.php'));
    }

    private function getSecurityControllerBuilder(bool $isLegacyTest): SecurityControllerBuilder
    {
        $compatUtil = $this->createMock(PhpCompatUtil::class);
        $compatUtil
            ->method('canUseAttributes')
            ->willReturn(!$isLegacyTest)
        ;

        return new SecurityControllerBuilder($compatUtil);
    }
}
