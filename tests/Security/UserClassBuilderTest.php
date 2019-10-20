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
    public function testAddUserInterfaceImplementation(UserClassConfiguration $userClassConfig, string $expectedFilename)
    {
        $sourceFilename = __DIR__.'/fixtures/source/'.($userClassConfig->isEntity() ? 'UserEntity.php' : 'UserModel.php');

        $manipulator = new ClassSourceManipulator(
            file_get_contents($sourceFilename),
            true
        );

        $classBuilder = new UserClassBuilder();
        $classBuilder->addUserInterfaceImplementation($manipulator, $userClassConfig);

        $expectedPath = __DIR__.'/fixtures/expected/'.$expectedFilename;
        if (!file_exists($expectedPath)) {
            throw new \Exception(sprintf('Expected file missing: "%s"', $expectedPath));
        }

        $this->assertSame(file_get_contents($expectedPath), $manipulator->getSourceCode());
    }

    public function getUserInterfaceTests()
    {
        yield 'entity_email_password' => [
            new UserClassConfiguration(true, 'email', true),
            'UserEntityEmailWithPassword.php',
        ];

        yield 'entity_username_password' => [
            new UserClassConfiguration(true, 'username', true),
            'UserEntityUsernameWithPassword.php',
        ];

        yield 'entity_user_name_password' => [
            new UserClassConfiguration(true, 'user_name', true),
            'UserEntityUser_nameWithPassword.php',
        ];

        yield 'entity_username_no_password' => [
            new UserClassConfiguration(true, 'username', false),
            'UserEntityUsernameNoPassword.php',
        ];

        yield 'model_email_password' => [
            new UserClassConfiguration(false, 'email', true),
            'UserModelEmailWithPassword.php',
        ];

        yield 'model_username_password' => [
            new UserClassConfiguration(false, 'username', true),
            'UserModelUsernameWithPassword.php',
        ];

        yield 'model_username_no_password' => [
            new UserClassConfiguration(false, 'username', false),
            'UserModelUsernameNoPassword.php',
        ];
    }
}
