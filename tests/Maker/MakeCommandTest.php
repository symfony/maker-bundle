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
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Yaml;

class MakeCommandTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeCommand::class;
    }

    public function getTestDetails(): \Generator
    {
        $supportsInvokable = Kernel::VERSION_ID >= 70300;

        yield 'it_makes_a_command_no_attributes' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) use ($supportsInvokable) {
                $runner->runMaker([
                    // command name
                    'app:foo',
                ], $supportsInvokable ? '--no-invokable' : '');

                $this->runCommandTest($runner, 'it_makes_a_command.php');
            }),
        ];

        yield 'it_makes_a_command_with_attributes' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) use ($supportsInvokable) {
                $runner->runMaker([
                    // command name
                    'app:foo',
                ], $supportsInvokable ? '--no-invokable' : '');

                $this->runCommandTest($runner, 'it_makes_a_command.php');

                $commandFileContents = file_get_contents($runner->getPath('src/Command/FooCommand.php'));

                self::assertStringContainsString('use Symfony\Component\Console\Attribute\AsCommand;', $commandFileContents);
                self::assertStringContainsString('#[AsCommand(', $commandFileContents);
            }),
        ];

        yield 'it_makes_a_command_in_custom_namespace' => [$this->createMakerTest()
            ->changeRootNamespace('Custom')
            ->run(function (MakerTestRunner $runner) use ($supportsInvokable) {
                $runner->writeFile(
                    'config/packages/dev/maker.yaml',
                    Yaml::dump(['maker' => ['root_namespace' => 'Custom']])
                );

                $runner->runMaker([
                    // command name
                    'app:foo',
                ], $supportsInvokable ? '--no-invokable' : '');

                $this->runCommandTest($runner, 'it_makes_a_command_in_custom_namespace.php');
            }),
        ];

        if ($supportsInvokable) {
            yield 'it_makes_an_invokable_command_by_default' => [$this->createMakerTest()
                ->run(function (MakerTestRunner $runner) {
                    $runner->runMaker([
                        // command name
                        'app:foo',
                        'foo',
                        '',
                        '',
                    ]);

                    $this->runCommandTest($runner, 'it_makes_a_command.php');

                    $commandFileContents = file_get_contents($runner->getPath('src/Command/FooCommand.php'));

                    self::assertStringContainsString('use Symfony\Component\Console\Attribute\AsCommand;', $commandFileContents);
                    self::assertStringContainsString('#[AsCommand(', $commandFileContents);
                    self::assertStringNotContainsString('extends Command', $commandFileContents);
                    self::assertStringContainsString('__invoke(', $commandFileContents);
                }),
            ];

            yield 'it_makes_an_invokable_and_configures_parameters' => [$this->createMakerTest()
                ->run(function (MakerTestRunner $runner) {
                    $runner->runMaker([
                        // command name
                        'app:foo',
                        'foo',
                        'bar', // Argument name
                        0, // Argument type (string)
                        'Adds a bar argument to your command', // Argument description
                        'no', // Has no default value
                        'yes', // Is nullable
                        'baz', // Second argument, will be required
                        1, // Second type (int)
                        'How many bazzes do you need?', // Second argument description
                        'no', // Second argument no default
                        'no', // Second argument not nullable
                        '', // Stop Arguments
                        'dry-run', // Option name
                        'd', // Option shortcut
                        0, // Option type (boolean)
                        'Perform a dry run?',
                        'no', // Default value (false)
                        '', // Stop option insertion
                    ]);

                    $commandFileContents = file_get_contents($runner->getPath('src/Command/FooCommand.php'));

                    self::assertStringContainsString('__invoke(', $commandFileContents);
                    self::assertStringContainsString('#[Argument(description: \'How many bazzes do you need?\')] int $baz,', $commandFileContents);
                    self::assertStringContainsString('#[Argument(description: \'Adds a bar argument to your command\')] ?string $bar = null', $commandFileContents);
                    self::assertStringContainsString('#[Option(description: \'Perform a dry run?\', shortcut: \'d\')] bool $dryRun = false', $commandFileContents);
                }),
            ];
        }
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
