<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeCommand;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeCommandTest extends MakerTestCase
{
    public function getTestDetails(): \Generator
    {
        yield 'command' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCommand::class),
            [
                // command name
                'app:foo',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCommand'),
        ];

        yield 'command_in_custom_root_namespace' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCommand::class),
            [
                // command name
                'app:foo',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCommandInCustomRootNamespace')
            ->changeRootNamespace('Custom'),
        ];

        yield 'command_with_attributes' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCommand::class),
            [
                // command name
                'app:foo',
            ])
            ->setRequiredPhpVersion(80000)
            ->addRequiredPackageVersion('symfony/console', '>=5.3')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCommand')
            ->assert(
                static function (string $output, string $directory) {
                    $commandFileContents = file_get_contents(sprintf('%s/src/Command/FooCommand.php', $directory));

                    self::assertStringContainsString('use Symfony\Component\Console\Attribute\AsCommand;', $commandFileContents);
                    self::assertStringContainsString('#[AsCommand(', $commandFileContents);
                }
            ),
        ];
    }
}
