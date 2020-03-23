<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Command\MakerCommand;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Util\NamespacesHelper;
use Symfony\Component\Console\Tester\CommandTester;

class MakerCommandTest extends TestCase
{
    public function testExceptionOnMissingDependencies()
    {
        $this->expectException(\Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException::class);
        $this->expectExceptionMessageRegExp('/composer require foo-package/');

        $maker = $this->createMock(MakerInterface::class);
        $maker->expects($this->once())
            ->method('configureDependencies')
            ->willReturnCallback(function (DependencyBuilder $depBuilder) {
                $depBuilder->addClassDependency('Foo', 'foo-package');
            });

        $fileManager = $this->createMock(FileManager::class);

        $namespacesHelper = new NamespacesHelper(['root_namespace' => 'App']);

        $command = new MakerCommand($maker, $fileManager, new Generator($fileManager, $namespacesHelper));
        // needed because it's normally set by the Application
        $command->setName('make:foo');
        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    public function testExceptionOnUnknownRootNamespace()
    {
        $maker = $this->createMock(MakerInterface::class);

        $fileManager = $this->createMock(FileManager::class);

        $namespacesHelper = new NamespacesHelper(['root' => 'Unknown']);

        $command = new MakerCommand($maker, $fileManager, new Generator($fileManager, $namespacesHelper));
        // needed because it's normally set by the Application
        $command->setName('make:foo');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertStringContainsString('using a namespace other than "Unknown"', $tester->getDisplay());
    }
}
