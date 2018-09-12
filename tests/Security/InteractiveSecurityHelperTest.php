<?php

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

    /**
     * @expectedException \Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException
     */
    public function testGuessEntryPointWithNonExistingFirewallThrowsException()
    {
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

        yield 'one_authenticator' => [
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
            ['security' => ['providers' => ['app_provider' => ['entity' => ['class' => 'App\\Entity\\User']]]]],
            'App\\Entity\\User',
            true,
        ];

        yield 'user_asked_user' => [
            ['security' => ['providers' => []]],
            'App\\Entity\\User',
            false,
        ];
    }
}