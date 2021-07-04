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
use Symfony\Bundle\MakerBundle\Security\UserClassBuilder;
use Symfony\Bundle\MakerBundle\Security\UserClassConfiguration;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Util\PhpCompatUtil;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\User;

class UserClassBuilderTest extends TestCase
{
    /**
     * @dataProvider getUserInterfaceTests
     */
    public function testAddUserInterfaceImplementation(UserClassConfiguration $userClassConfig, string $expectedFilename): void
    {
        if (!interface_exists(PasswordAuthenticatedUserInterface::class)) {
            self::markTestSkipped('Requires Symfony >= 5.3');
        }

        $manipulator = $this->getClassSourceManipulator($userClassConfig);

        $mockPhpCompatUtil = $this->createMock(PhpCompatUtil::class);

        $classBuilder = new UserClassBuilder($mockPhpCompatUtil);
        $classBuilder->addUserInterfaceImplementation($manipulator, $userClassConfig);

        $expectedPath = $this->getExpectedPath($expectedFilename);

        self::assertStringEqualsFile($expectedPath, $manipulator->getSourceCode());
    }

    public function getUserInterfaceTests(): \Generator
    {
        yield 'entity_with_email_as_identifier' => [
            new UserClassConfiguration(true, 'email', true),
            'UserEntityWithEmailAsIdentifier.php',
        ];

        yield 'entity_with_password' => [
            new UserClassConfiguration(true, 'userIdentifier', true),
            'UserEntityWithPassword.php',
        ];

        yield 'entity_with_user_identifier_as_identifier' => [
            new UserClassConfiguration(true, 'user_identifier', true),
            'UserEntityWithUser_IdentifierAsIdentifier.php',
        ];

        yield 'entity_without_password' => [
            new UserClassConfiguration(true, 'userIdentifier', false),
            'UserEntityWithoutPassword.php',
        ];

        yield 'model_with_email_as_identifier' => [
            new UserClassConfiguration(false, 'email', true),
            'UserModelWithEmailAsIdentifier.php',
        ];

        yield 'model_with_password' => [
            new UserClassConfiguration(false, 'userIdentifier', true),
            'UserModelWithPassword.php',
        ];

        yield 'model_without_password' => [
            new UserClassConfiguration(false, 'userIdentifier', false),
            'UserModelWithoutPassword.php',
        ];
    }

    /**
     * Covers Symfony <= 5.2 UserInterface::getUsername().
     *
     * In Symfony 5.3, getUsername was replaced with getUserIdentifier()
     *
     * @dataProvider legacyUserInterfaceGetUsernameDataProvider
     */
    public function testLegacyUserInterfaceGetUsername(UserClassConfiguration $userClassConfig, string $expectedFilename): void
    {
        if (method_exists(User::class, 'getUserIdentifier')) {
            self::markTestSkipped();
        }

        $manipulator = $this->getClassSourceManipulator($userClassConfig);

        $mockPhpCompatUtil = $this->createMock(PhpCompatUtil::class);

        $classBuilder = new UserClassBuilder($mockPhpCompatUtil);
        $classBuilder->addUserInterfaceImplementation($manipulator, $userClassConfig);

        $expectedPath = $this->getExpectedPath($expectedFilename, 'legacy_get_username');

        self::assertStringEqualsFile($expectedPath, $manipulator->getSourceCode());
    }

    public function legacyUserInterfaceGetUsernameDataProvider(): \Generator
    {
        yield 'entity_with_get_username' => [
            new UserClassConfiguration(true, 'username', false),
            'UserEntityGetUsername.php',
        ];

        yield 'model_with_get_username' => [
            new UserClassConfiguration(false, 'username', false),
            'UserModelGetUsername.php',
        ];

        yield 'model_with_email_as_username' => [
            new UserClassConfiguration(false, 'email', false),
            'UserModelWithEmailAsUsername.php',
        ];
    }

    /**
     * Covers Symfony <= 5.2 UserInterface::getPassword().
     *
     * In Symfony 5.3, getPassword was moved from UserInterface::class to the
     * new PasswordAuthenticatedUserInterface::class.
     *
     * @dataProvider legacyUserInterfaceGetPasswordDataProvider
     */
    public function testLegacyUserInterfaceGetPassword(UserClassConfiguration $userClassConfig, string $expectedFilename): void
    {
        if (interface_exists(PasswordAuthenticatedUserInterface::class)) {
            self::markTestSkipped();
        }

        $manipulator = $this->getClassSourceManipulator($userClassConfig);

        $mockPhpCompatUtil = $this->createMock(PhpCompatUtil::class);

        $classBuilder = new UserClassBuilder($mockPhpCompatUtil);
        $classBuilder->addUserInterfaceImplementation($manipulator, $userClassConfig);

        $expectedPath = $this->getExpectedPath($expectedFilename, 'legacy_get_password');

        self::assertStringEqualsFile($expectedPath, $manipulator->getSourceCode());
    }

    public function legacyUserInterfaceGetPasswordDataProvider(): \Generator
    {
        yield 'entity_with_password' => [
            new UserClassConfiguration(true, 'username', true),
            'UserEntityWithPassword.php',
        ];

        yield 'entity_without_password' => [
            new UserClassConfiguration(true, 'username', false),
            'UserEntityNoPassword.php',
        ];

        yield 'model_with_password' => [
            new UserClassConfiguration(false, 'username', true),
            'UserModelWithPassword.php',
        ];

        yield 'model_without_password' => [
            new UserClassConfiguration(false, 'username', false),
            'UserModelNoPassword.php',
        ];
    }

    private function getClassSourceManipulator(UserClassConfiguration $userClassConfiguration): ClassSourceManipulator
    {
        $sourceFilename = __DIR__.'/fixtures/source/'.($userClassConfiguration->isEntity() ? 'UserEntity.php' : 'UserModel.php');

        return new ClassSourceManipulator(
            file_get_contents($sourceFilename),
            true
        );
    }

    private function getExpectedPath(string $expectedFilename, string $subDirectory = null): string
    {
        $basePath = __DIR__.'/fixtures/expected';

        $expectedPath = null === $subDirectory ? sprintf('%s/%s', $basePath, $expectedFilename) : sprintf('%s/%s/%s', $basePath, $subDirectory, $expectedFilename);

        if (!file_exists($expectedPath)) {
            throw new \Exception(sprintf('Expected file missing: "%s"', $expectedPath));
        }

        return $expectedPath;
    }
}
