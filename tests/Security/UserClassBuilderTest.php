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
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGrantedContext;

class UserClassBuilderTest extends TestCase
{
    /**
     * @dataProvider getUserInterfaceTests
     */
    public function testAddUserInterfaceImplementation(UserClassConfiguration $userClassConfig, string $expectedFilename)
    {
        $manipulator = $this->getClassSourceManipulator($userClassConfig);

        $classBuilder = new UserClassBuilder();
        $classBuilder->addUserInterfaceImplementation($manipulator, $userClassConfig, $userClassConfig->isEntity() ? 'App\Entity\User' : 'App\Security\User');

        $expectedPath = $this->getExpectedPath($expectedFilename, null);
        $expectedSource = file_get_contents($expectedPath);

        if (!class_exists(IsGrantedContext::class)) {
            $expectedSource = preg_replace('/\n\n(.+\n)+.+function __serialize[^}]++}/', '', $expectedSource);
        }

        if (!method_exists(UserInterface::class, 'eraseCredentials')) {
            $expectedSource = preg_replace('/\n\n(.+\n)+.+function eraseCredentials[^}]++}/', '', $expectedSource);
        }

        self::assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public static function getUserInterfaceTests(): \Generator
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
     * @dataProvider getUniqueConstraintNameTests
     */
    public function testUniqueConstraintNameIsPrefixedWithUserClassName(string $userClass, string $identityPropertyName, string $expectedName)
    {
        $userClassConfig = new UserClassConfiguration(true, $identityPropertyName, true);
        $manipulator = $this->getClassSourceManipulator($userClassConfig);

        $classBuilder = new UserClassBuilder();
        $classBuilder->addUserInterfaceImplementation($manipulator, $userClassConfig, $userClass);

        self::assertStringContainsString(\sprintf('#[ORM\UniqueConstraint(name: \'%s\', fields: [\'%s\'])]', $expectedName, $identityPropertyName), $manipulator->getSourceCode());
    }

    public static function getUniqueConstraintNameTests(): \Generator
    {
        yield 'class_name_with_one_word' => ['App\Entity\Admin', 'email', 'ADMIN_UNIQ_IDENTIFIER_EMAIL'];

        yield 'class_name_with_several_words' => ['App\Entity\BackOfficeUser', 'userIdentifier', 'BACK_OFFICE_USER_UNIQ_IDENTIFIER_USER_IDENTIFIER'];

        yield 'class_in_sub_namespace' => ['App\Entity\BackOffice\Admin', 'username', 'ADMIN_UNIQ_IDENTIFIER_USERNAME'];
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
