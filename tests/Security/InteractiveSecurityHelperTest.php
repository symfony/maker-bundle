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
use Symfony\Bundle\MakerBundle\Security\InteractiveSecurityHelper;
use Symfony\Component\Console\Style\SymfonyStyle;

class InteractiveSecurityHelperTest extends TestCase
{
    /**
     * @dataProvider getFirewallNameTests
     */
    public function testGuessFirewallName(array $securityData, string $expectedFirewallName, $multipleValues = false)
    {
        /** @var SymfonyStyle|\PHPUnit_Framework_MockObject_MockObject $io */
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->exactly(false === $multipleValues ? 0 : 1))
            ->method('choice')
            ->willReturn($expectedFirewallName);

        $helper = new InteractiveSecurityHelper();
        $this->assertEquals(
            $expectedFirewallName,
            $helper->guessFirewallName($io, $securityData)
        );
    }

    public function getFirewallNameTests()
    {
        yield 'empty_security' => [
            [],
            'main',
        ];

        yield 'no_firewall' => [
            ['security' => ['firewalls' => []]],
            'main',
        ];

        yield 'no_secured_firewall' => [
            ['security' => ['firewalls' => ['dev' => ['security' => false]]]],
            'main',
        ];

        yield 'main_firewall' => [
            ['security' => ['firewalls' => ['dev' => ['security' => false], 'main' => null]]],
            'main',
        ];

        yield 'foo_firewall' => [
            ['security' => ['firewalls' => ['dev' => ['security' => false], 'foo' => null]]],
            'foo',
        ];

        yield 'foo_bar_firewalls_1' => [
            ['security' => ['firewalls' => ['dev' => ['security' => false], 'foo' => null, 'bar' => null]]],
            'foo',
            true,
        ];

        yield 'foo_bar_firewalls_2' => [
            ['security' => ['firewalls' => ['dev' => ['security' => false], 'foo' => null, 'bar' => null]]],
            'bar',
            true,
        ];
    }

    public function testGuessEntryPointWithNonExistingFirewallThrowsException()
    {
        $this->expectException(\Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException::class);

        /** @var SymfonyStyle|\PHPUnit_Framework_MockObject_MockObject $io */
        $io = $this->createMock(SymfonyStyle::class);

        $helper = new InteractiveSecurityHelper();
        $helper->guessEntryPoint($io, [], '', 'foo');
    }

    /**
     * @dataProvider getEntryPointTests
     */
    public function testGuestEntryPoint(array $securityData, string $firewallName, bool $multipleAuthenticators = false)
    {
        /** @var SymfonyStyle|\PHPUnit_Framework_MockObject_MockObject $io */
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->exactly(false === $multipleAuthenticators ? 0 : 1))
            ->method('choice');

        $helper = new InteractiveSecurityHelper();
        $helper->guessEntryPoint($io, $securityData, 'App\\Security\\NewAuthenticator', $firewallName);
    }

    public function getEntryPointTests()
    {
        yield 'no_guard' => [
            ['security' => ['firewalls' => ['main' => []]]],
            'main',
        ];

        yield 'no_authenticators_key' => [
            ['security' => ['firewalls' => ['main' => ['guard' => []]]]],
            'main',
        ];

        yield 'no_authenticator' => [
            ['security' => ['firewalls' => ['main' => ['guard' => ['authenticators' => []]]]]],
            'main',
        ];

        yield 'one_authenticator' => [
            ['security' => ['firewalls' => ['main' => ['guard' => ['authenticators' => ['App\\Security\\Authenticator']]]]]],
            'main',
            true,
        ];

        yield 'one_authenticator_entry_point' => [
            ['security' => ['firewalls' => ['main' => ['guard' => ['entry_point' => 'App\\Security\\Authenticator', 'authenticators' => ['App\\Security\\Authenticator']]]]]],
            'main',
        ];
    }

    /**
     * @dataProvider getUserClassTests
     */
    public function testGuessUserClass(array $securityData, string $expectedUserClass, bool $userClassAutomaticallyGuessed)
    {
        /** @var SymfonyStyle|\PHPUnit_Framework_MockObject_MockObject $io */
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->exactly(true === $userClassAutomaticallyGuessed ? 0 : 1))
            ->method('ask')
            ->willReturn($expectedUserClass);

        $helper = new InteractiveSecurityHelper();
        $this->assertEquals(
            $expectedUserClass,
            $helper->guessUserClass($io, $securityData)
        );
    }

    public function getUserClassTests()
    {
        yield 'user_from_provider' => [
            ['app_provider' => ['entity' => ['class' => 'App\\Entity\\User']]],
            'App\\Entity\\User',
            true,
        ];

        yield 'multiple_providers' => [
            ['provider_1' => ['id' => 'app.provider_1'], 'provider_2' => ['id' => 'app.provider_2']],
            'App\\Entity\\User',
            false,
        ];

        yield 'no_provider' => [
            [[]],
            'App\\Entity\\User',
            false,
        ];
    }

    /**
     * @dataProvider getUsernameFieldsTest
     */
    public function testGuessUserNameField(array $providers, string $expectedUsernameField, bool $fieldAutomaticallyGuessed, string $class = '', array $choices = [])
    {
        /** @var SymfonyStyle|\PHPUnit_Framework_MockObject_MockObject $io */
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->exactly(true === $fieldAutomaticallyGuessed ? 0 : 1))
            ->method('choice')
            ->with(sprintf('Which field on your <fg=yellow>%s</> class will people enter when logging in?', $class), $choices, 'username')
            ->willReturn($expectedUsernameField);

        $interactiveSecurityHelper = new InteractiveSecurityHelper();
        $this->assertEquals(
            $expectedUsernameField,
            $interactiveSecurityHelper->guessUserNameField($io, $class, $providers)
        );
    }

    public function getUsernameFieldsTest()
    {
        yield 'guess_with_providers' => [
            'providers' => ['app_provider' => ['entity' => ['property' => 'userEmail']]],
            'expectedUsernameField' => 'userEmail',
            true,
        ];

        yield 'guess_with_providers_and_custom_repository_method' => [
            'providers' => ['app_provider' => ['entity' => null]],
            'expectedUsernameField' => 'email',
            true,
            FixtureClass::class,
        ];

        yield 'guess_fixture_class' => [
            'providers' => [],
            'expectedUsernameField' => 'email',
            true,
            FixtureClass::class,
        ];

        yield 'guess_fixture_class_2' => [
            'providers' => [],
            'expectedUsernameField' => 'username',
            true,
            FixtureClass2::class,
        ];

        yield 'guess_fixture_class_3' => [
            'providers' => [],
            'expectedUsernameField' => 'username',
            false,
            FixtureClass3::class,
            ['username', 'email'],
        ];
    }

    /**
     * @dataProvider guessEmailFieldTest
     */
    public function testGuessEmailField(string $expectedEmailField, bool $fieldAutomaticallyGuessed, string $class = '', array $choices = [])
    {
        /** @var SymfonyStyle|\PHPUnit_Framework_MockObject_MockObject $io */
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->exactly(true === $fieldAutomaticallyGuessed ? 0 : 1))
            ->method('choice')
            ->with(sprintf('Which field on your <fg=yellow>%s</> class holds the email address?', $class), $choices, null)
            ->willReturn($expectedEmailField);

        $interactiveSecurityHelper = new InteractiveSecurityHelper();
        $this->assertEquals(
            $expectedEmailField,
            $interactiveSecurityHelper->guessEmailField($io, $class)
        );
    }

    public function guessEmailFieldTest()
    {
        yield 'guess_fixture_class' => [
            'expectedEmailField' => 'email',
            true,
            FixtureClass::class,
        ];

        yield 'guess_fixture_class_2' => [
            'expectedEmailField' => 'myEmail',
            false,
            FixtureClass4::class,
            ['myEmail'],
        ];
    }

    /**
     * @dataProvider guessPasswordSetterTest
     */
    public function testGuessPasswordSetter(string $expectedPasswordSetter, bool $automaticallyGuessed, string $class = '', array $choices = [])
    {
        /** @var SymfonyStyle|\PHPUnit_Framework_MockObject_MockObject $io */
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->exactly(true === $automaticallyGuessed ? 0 : 1))
            ->method('choice')
            ->with(sprintf('Which method on your <fg=yellow>%s</> class can be used to set the encoded password (e.g. setPassword())?', $class), $choices, null)
            ->willReturn($expectedPasswordSetter);

        $interactiveSecurityHelper = new InteractiveSecurityHelper();
        $this->assertEquals(
            $expectedPasswordSetter,
            $interactiveSecurityHelper->guessPasswordSetter($io, $class)
        );
    }

    public function guessPasswordSetterTest()
    {
        yield 'guess_fixture_class' => [
            'expectedPasswordSetter' => 'setPassword',
            true,
            FixtureClass5::class,
        ];

        yield 'guess_fixture_class_2' => [
            'expectedPasswordSetter' => 'setEncodedPassword',
            false,
            FixtureClass6::class,
            ['setEncodedPassword'],
        ];
    }

    /**
     * @dataProvider guessEmailGetterTest
     */
    public function testGuessEmailGetter(string $expectedEmailGetter, string $emailAttribute, bool $automaticallyGuessed, string $class = '', array $choices = [])
    {
        /** @var SymfonyStyle|\PHPUnit_Framework_MockObject_MockObject $io */
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->exactly(true === $automaticallyGuessed ? 0 : 1))
            ->method('choice')
            ->with(sprintf('Which method on your <fg=yellow>%s</> class can be used to get the email address (e.g. getEmail())?', $class), $choices, null)
            ->willReturn($expectedEmailGetter);

        $interactiveSecurityHelper = new InteractiveSecurityHelper();
        $this->assertEquals(
            $expectedEmailGetter,
            $interactiveSecurityHelper->guessEmailGetter($io, $class, $emailAttribute)
        );
    }

    public function guessEmailGetterTest()
    {
        yield 'guess_fixture_class' => [
            'expectedPasswordSetter' => 'getEmail',
            'email',
            true,
            FixtureClass7::class,
        ];

        yield 'guess_fixture_class_different_property' => [
            'expectedPasswordSetter' => 'getEmail',
            'myEmail',
            false,
            FixtureClass7::class,
            ['getEmail'],
        ];

        yield 'guess_fixture_class_2' => [
            'expectedPasswordSetter' => 'getMyEmail',
            '',
            false,
            FixtureClass8::class,
            ['getMyEmail'],
        ];
    }
}

class FixtureClass
{
    private $email;
}

class FixtureClass2
{
    private $username;
}

class FixtureClass3
{
    private $username;
    private $email;
}

class FixtureClass4
{
    private $myEmail;
}

class FixtureClass5
{
    public function setPassword()
    {
    }
}

class FixtureClass6
{
    public function setEncodedPassword()
    {
    }
}

class FixtureClass7
{
    public function getEmail()
    {
    }
}

class FixtureClass8
{
    public function getMyEmail()
    {
    }
}
