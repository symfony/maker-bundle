<?php

namespace Symfony\Bundle\MakerBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Security\InteractiveSecurityHelper;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InteractiveSecurityHelperTest extends TestCase
{
    /**
     * @dataProvider getFirewallNameTests
     *
     * @param array  $securityData
     * @param string $expectedFirewallName
     * @param bool   $multipleValues
     */
    public function testGuessFirewallName(array $securityData, string $expectedFirewallName, $multipleValues = false)
    {
        /** @var InputInterface|\PHPUnit_Framework_MockObject_MockObject $input */
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())
            ->method('setOption')
            ->with('firewall-name', $expectedFirewallName);

        /** @var SymfonyStyle|\PHPUnit_Framework_MockObject_MockObject $io */
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->exactly(false === $multipleValues ? 0 : 1))
            ->method('choice')
            ->willReturn($expectedFirewallName);

        $helper = new InteractiveSecurityHelper();
        $helper->guessFirewallName($input, $io, $securityData);
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
    public function testGuessEntryPointWithNoFirewallNameThrowsException()
    {
        /** @var InputInterface|\PHPUnit_Framework_MockObject_MockObject $input */
        $input = $this->createMock(InputInterface::class);

        /** @var SymfonyStyle|\PHPUnit_Framework_MockObject_MockObject $io */
        $io = $this->createMock(SymfonyStyle::class);

        /** @var Generator|\PHPUnit_Framework_MockObject_MockObject $generator */
        $generator = $this->createMock(Generator::class);

        $helper = new InteractiveSecurityHelper();
        $helper->guessEntryPoint($input, $io, $generator, []);
    }

    /**
     * @expectedException \Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException
     */
    public function testGuessEntryPointWithNonExistingFirewallThrowsException()
    {
        /** @var InputInterface|\PHPUnit_Framework_MockObject_MockObject $input */
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())
            ->method('getOption')
            ->with('firewall-name')
            ->willReturn('foo');

        /** @var SymfonyStyle|\PHPUnit_Framework_MockObject_MockObject $io */
        $io = $this->createMock(SymfonyStyle::class);

        /** @var Generator|\PHPUnit_Framework_MockObject_MockObject $generator */
        $generator = $this->createMock(Generator::class);

        $helper = new InteractiveSecurityHelper();
        $helper->guessEntryPoint($input, $io, $generator, []);
    }

    /**
     * @dataProvider getEntryPointTests
     */
    public function testGuestEntryPoint(array $securityData, string $firewallName, bool $multipleAuthenticators = false)
    {
        /** @var InputInterface|\PHPUnit_Framework_MockObject_MockObject $input */
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())
            ->method('getOption')
            ->with('firewall-name')
            ->willReturn($firewallName);

        $input->expects($this->exactly(false === $multipleAuthenticators ? 0 : 1))
            ->method('getArgument')
            ->with('authenticator-class')
            ->willReturn('NewAuthenticator');


        /** @var SymfonyStyle|\PHPUnit_Framework_MockObject_MockObject $io */
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->exactly(false === $multipleAuthenticators ? 0 : 1))
            ->method('choice');

        /** @var Generator|\PHPUnit_Framework_MockObject_MockObject $generator */
        $generator = $this->createMock(Generator::class);
        $generator->expects($this->exactly(false === $multipleAuthenticators ? 0 : 1))
            ->method('createClassNameDetails')
            ->willReturn(new ClassNameDetails('App\\Security\\NewAuthenticator', 'App\\Security'));

        $helper = new InteractiveSecurityHelper();
        $helper->guessEntryPoint($input, $io, $generator, $securityData);
    }

    public function getEntryPointTests()
    {
        yield 'no_guard' => [
            ['security' => ['firewalls' => ['main' => []]]],
            'main'
        ];

        yield 'no_authenticators_key' => [
            ['security' => ['firewalls' => ['main' => ['guard' => []]]]],
            'main'
        ];

        yield 'no_authenticator' => [
            ['security' => ['firewalls' => ['main' => ['guard' => ['authenticators' => []]]]]],
            'main'
        ];

        yield 'one_authenticator' => [
            ['security' => ['firewalls' => ['main' => ['guard' => ['authenticators' => ['App\\Security\\Authenticator']]]]]],
            'main',
            true
        ];
    }
}