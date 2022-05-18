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
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;
use Symfony\Component\Yaml\Yaml;

class MakeCommandTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeCommand::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_makes_a_command_no_attributes' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // command name
                    'app:foo',
                ]);

                $this->runCommandTest($runner, 'it_makes_a_command.php');
            }),
        ];

        yield 'it_makes_a_command_with_attributes' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // command name
                    'app:foo',
                ]);

                $this->runCommandTest($runner, 'it_makes_a_command.php');

                $commandFileContents = file_get_contents($runner->getPath('src/Command/FooCommand.php'));

                self::assertStringContainsString('use Symfony\Component\Console\Attribute\AsCommand;', $commandFileContents);
                self::assertStringContainsString('#[AsCommand(', $commandFileContents);
            }),
        ];

        yield 'it_makes_a_command_in_custom_namespace' => [$this->createMakerTest()
            ->changeRootNamespace('Custom')
            ->run(function (MakerTestRunner $runner) {
                $runner->writeFile(
                    'config/packages/dev/maker.yaml',
                    Yaml::dump(['maker' => ['root_namespace' => 'Custom']])
                );

                $runner->runMaker([
                    // command name
                    'app:foo',
                ]);

                $this->runCommandTest($runner, 'it_makes_a_command_in_custom_namespace.php');
            }),
        ];
    }

    private function runCommandTest(MakerTestRunner $runner, string $filename): void
    {
        $runner->copy(
            'make-command/tests/'.$filename,
            'tests/GeneratedCommandTest.php'
        );

        $runner->runTests();
    }
}
