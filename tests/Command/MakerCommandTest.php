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
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Util\TemplateLinter;
use Symfony\Component\Console\Tester\CommandTester;

class MakerCommandTest extends TestCase
{
    public function testExceptionOnMissingDependencies(): void
    {
        $this->expectException(RuntimeCommandException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/composer require foo-package/');
        }

        $maker = $this->createMock(MakerInterface::class);
        $maker
            ->expects(self::once())
            ->method('configureDependencies')
            ->willReturnCallback(static function (DependencyBuilder $depBuilder) {
                $depBuilder->addClassDependency('Foo', 'foo-package');
            });

        $fileManager = $this->createMock(FileManager::class);

        $command = new MakerCommand($maker, $fileManager, new Generator($fileManager, 'App'), new TemplateLinter());
        // needed because it's normally set by the Application
        $command->setName('make:foo');
        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    public function testExceptionOnUnknownRootNamespace(): void
    {
        $maker = $this->createMock(MakerInterface::class);

        $fileManager = $this->createMock(FileManager::class);

        $command = new MakerCommand($maker, $fileManager, new Generator($fileManager, 'Unknown'), new TemplateLinter());
        // needed because it's normally set by the Application
        $command->setName('make:foo');
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertStringContainsString('using a namespace other than "Unknown"', $tester->getDisplay());
    }
}
