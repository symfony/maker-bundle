<?php

namespace Symfony\Bundle\MakerBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Command\MakerCommand;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Component\Console\Tester\CommandTester;

class MakerCommandTest extends TestCase
{
    /**
     * @expectedException \Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException
     * @expectedExceptionMessageRegExp /composer require foo-package/
     */
    public function testExceptionOnMissingDependencies()
    {
        $maker = $this->createMock(MakerInterface::class);
        $maker->expects($this->once())
            ->method('configureDependencies')
            ->willReturnCallback(function(DependencyBuilder $depBuilder) {
                $depBuilder->addClassDependency('Foo', 'foo-package');
            });

        $fileManager = $this->createMock(FileManager::class);

        $command = new MakerCommand($maker, $fileManager, new Generator($fileManager, 'App', true));
        // needed because it's normally set by the Application
        $command->setName('make:foo');
        $tester = new CommandTester($command);
        $tester->execute(array());
    }

    public function testExceptionOnUnknownRootNamespace()
    {
        $maker = $this->createMock(MakerInterface::class);

        $fileManager = $this->createMock(FileManager::class);

        $command = new MakerCommand($maker, $fileManager, new Generator($fileManager, 'Unknown', true));
        // needed because it's normally set by the Application
        $command->setName('make:foo');
        $tester = new CommandTester($command);
        $tester->execute(array());

        $this->assertContains('using a namespace other than "Unknown"', $tester->getDisplay());
    }
}
