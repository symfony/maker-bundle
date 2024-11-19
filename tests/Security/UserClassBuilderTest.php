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

class UserClassBuilderTest extends TestCase
{
    /**
     * @dataProvider getUserInterfaceTests
     */
    public function testAddUserInterfaceImplementation(UserClassConfiguration $userClassConfig, string $expectedFilename): void
    {
        $manipulator = $this->getClassSourceManipulator($userClassConfig);

        $classBuilder = new UserClassBuilder();
        $classBuilder->addUserInterfaceImplementation($manipulator, $userClassConfig);

        $expectedPath = $this->getExpectedPath($expectedFilename, null);
        $expectedSource = file_get_contents($expectedPath);

        self::assertSame($expectedSource, $manipulator->getSourceCode());
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

    private function getClassSourceManipulator(UserClassConfiguration $userClassConfiguration): ClassSourceManipulator
    {
        $sourceFilename = __DIR__.'/fixtures/source/'.($userClassConfiguration->isEntity() ? 'UserEntity.php' : 'UserModel.php');

        return new ClassSourceManipulator(
            file_get_contents($sourceFilename),
            true
        );
    }

    private function getExpectedPath(string $expectedFilename): string
    {
        $basePath = __DIR__.'/fixtures/expected';

        $expectedPath = \sprintf('%s/%s', $basePath, $expectedFilename);

        if (!file_exists($expectedPath)) {
            throw new \Exception(\sprintf('Expected file missing: "%s"', $expectedPath));
        }

        return $expectedPath;
    }
}
