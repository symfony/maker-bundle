<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Test;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MakerTestDetailsTest extends TestCase
{
    public function testAddExtraDependencies(): void
    {
        $details = new MakerTestDetails($this->createMock(MakerInterface::class));

        $details->addExtraDependencies('twig');

        self::assertSame(['twig'], $details->getExtraDependencies());

        $details->addExtraDependencies('ux', 'mercure');

        self::assertSame(['twig', 'ux', 'mercure'], $details->getExtraDependencies());
    }

    public function testAddRequiredPackageVersions(): void
    {
        $details = new MakerTestDetails($this->createMock(MakerInterface::class));

        $details->addRequiredPackageVersion('twig', '4.x');

        self::assertSame([['name' => 'twig', 'version_constraint' => '4.x']], $details->getRequiredPackageVersions());

        $details->addRequiredPackageVersion('maker', '1.x');
        self::assertSame([
            ['name' => 'twig', 'version_constraint' => '4.x'],
            ['name' => 'maker', 'version_constraint' => '1.x'],
        ], $details->getRequiredPackageVersions());
    }

    public function testGetDependencies(): void
    {
        $details = new MakerTestDetails(new TestMakerFixture());

        $details->addExtraDependencies('twig');

        self::assertSame(['security', 'router', 'twig'], $details->getDependencies());
    }
}

/**
 * @method string getCommandDescription()
 */
class TestMakerFixture implements MakerInterface
{
    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(Voter::class, 'security');
        $dependencies->addClassDependency(Route::class, 'router', devDependency: true);
    }

    public static function getCommandName(): string
    {
        return 'test';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
    }
}
