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

    public static function getTestDetails(): \Generator
    {
        yield 'it_makes_a_command_no_attributes' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // command name
                    'app:foo',
                    // no argument
                    '',
                    // no option
                    '',
                ]);

                self::runCommandTest($runner, 'it_makes_a_command.php');
            }),
        ];

        yield 'it_makes_a_command_with_attributes' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // command name
                    'app:foo',
                    // no argument
                    '',
                    // no option
                    '',
                ]);

                self::runCommandTest($runner, 'it_makes_a_command.php');

                $commandFileContents = file_get_contents($runner->getPath('src/Command/FooCommand.php'));

                self::assertStringContainsString('use Symfony\Component\Console\Attribute\AsCommand;', $commandFileContents);
                self::assertStringContainsString('#[AsCommand(', $commandFileContents);
            }),
        ];

        yield 'it_makes_a_command_in_custom_namespace' => [self::buildMakerTest()
            ->changeRootNamespace('Custom')
            ->run(static function (MakerTestRunner $runner) {
                $runner->writeFile(
                    'config/packages/dev/maker.yaml',
                    Yaml::dump(['maker' => ['root_namespace' => 'Custom']])
                );

                $runner->runMaker([
                    // command name
                    'app:foo',
                    // no argument
                    '',
                    // no option
                    '',
                ]);

                self::runCommandTest($runner, 'it_makes_a_command_in_custom_namespace.php');
            }),
        ];

        yield 'it_makes_a_command_non_interactively' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], 'app:foo --no-interaction');

                self::assertStringContainsString('Success', $output);

                $commandFileContents = file_get_contents($runner->getPath('src/Command/FooCommand.php'));

                // interact() does not run without a terminal, so no parameter is collected and the
                // sample ones are generated, exactly as before this was interactive
                self::assertStringContainsString("#[Argument('Argument description')] string \$arg = '',", $commandFileContents);
                self::assertStringContainsString("#[Option('Option description')] bool \$enable = false,", $commandFileContents);
            }),
        ];

        yield 'it_makes_a_command_with_arguments_and_options' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                self::writeEnums($runner);

                $runner->runMaker([
                    // command name
                    'app:foo',
                    // $io is the first parameter of __invoke(), so the name is asked again
                    'io',
                    // argument name, type, description, required
                    'recipient', 'string', 'Who to greet', 'y',
                    // argument name, type, description, required
                    'times', 'int', 'How many times', '',
                    // argument name, type, description: no "required" question here, an argument
                    // following an optional one can only be optional; and no more arguments are
                    // asked for after an array one, which takes every remaining value
                    'tags', 'array', 'Tags to add',
                    // taken by an argument, so the name is asked again
                    'times',
                    // option name, type, description
                    'dry-run', 'bool', 'Do not send anything',
                    // option name, type, enum class, description
                    'format', 'enum', 'App\\Enum\\Format', 'Output format',
                    // no more options
                    '',
                ]);

                self::runCommandTest($runner, 'it_makes_a_command_with_arguments_and_options.php');

                $commandFileContents = file_get_contents($runner->getPath('src/Command/FooCommand.php'));

                self::assertStringContainsString("#[Argument('Who to greet')] string \$recipient,", $commandFileContents);
                self::assertStringContainsString("#[Argument('How many times')] int \$times = 0,", $commandFileContents);
                self::assertStringContainsString("#[Argument('Tags to add')] array \$tags = [],", $commandFileContents);
                self::assertStringContainsString("#[Option('Do not send anything')] bool \$dryRun = false,", $commandFileContents);
                self::assertStringContainsString('use App\Enum\Format;', $commandFileContents);
                self::assertStringContainsString("#[Option('Output format')] ?Format \$format = null,", $commandFileContents);
                self::assertSame(1, substr_count($commandFileContents, '$times = '));
                self::assertStringNotContainsString('$arg', $commandFileContents);
            }),
        ];

        yield 'it_makes_a_command_from_the_command_line' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                self::writeEnums($runner);

                // no answer is given: passing any of them on the command line skips the questions
                $runner->runMaker([], 'app:foo --argument=recipient --argument=times?:int --argument=tags?:array --option=dry-run --option=retries:int --option=format:Format --option=mode:Option');

                self::runCommandTest($runner, 'it_makes_a_command_with_arguments_and_options.php');

                $commandFileContents = file_get_contents($runner->getPath('src/Command/FooCommand.php'));

                self::assertStringContainsString("#[Argument('Recipient')] string \$recipient,", $commandFileContents);
                self::assertStringContainsString("#[Argument('Times')] int \$times = 0,", $commandFileContents);
                self::assertStringContainsString("#[Argument('Tags')] array \$tags = [],", $commandFileContents);
                self::assertStringContainsString("#[Option('Dry Run')] bool \$dryRun = false,", $commandFileContents);
                self::assertStringContainsString("#[Option('Retries')] int \$retries = 0,", $commandFileContents);
                // the short name of the enum is resolved within src/
                self::assertStringContainsString('use App\Enum\Format;', $commandFileContents);
                self::assertStringContainsString("#[Option('Format')] ?Format \$format = null,", $commandFileContents);
                // "Option" is taken by the attribute
                self::assertStringContainsString('use App\Enum\Option as EnumOption;', $commandFileContents);
                self::assertStringContainsString("#[Option('Mode')] ?EnumOption \$mode = null,", $commandFileContents);
                self::assertStringNotContainsString('$arg', $commandFileContents);
            }),
        ];

        yield 'it_rejects_invalid_definitions' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $invalidDefinitions = [
                    '--argument=:string' => 'is missing a name',
                    '--argument=1st' => '"1st" is not a valid PHP variable name',
                    '--argument=name:nope' => 'Invalid type "nope"',
                    // the "?" belongs to the name, as in a PHP array shape
                    '--argument=count:int?' => 'Invalid type "int?"',
                    // $io is the first parameter of __invoke()
                    '--argument=io' => 'already has a "$io" parameter',
                    '--argument=name --option=name' => 'already has a "$name" parameter',
                    // the console component would only refuse these once the generated command runs
                    '--argument=name? --argument=other' => 'The argument "other" cannot be required',
                    '--argument=names:array --argument=other?' => 'No argument can follow the array argument "names"',
                    '--option=force?' => 'The option "force?" cannot have a "?"',
                ];

                foreach ($invalidDefinitions as $definitions => $expectedError) {
                    $output = $runner->runMaker(
                        [],
                        \sprintf('--no-interaction app:foo %s', $definitions),
                        allowedToFail: true
                    );

                    self::assertStringContainsString($expectedError, $output, \sprintf('"%s" was not rejected.', $definitions));
                    // nothing is written before every definition has been parsed
                    self::assertFileDoesNotExist($runner->getPath('src/Command/FooCommand.php'));
                }
            }),
        ];
    }

    private static function writeEnums(MakerTestRunner $runner): void
    {
        $runner->writeFile('src/Enum/Format.php', <<<'PHP'
            <?php

            namespace App\Enum;

            enum Format: string
            {
                case Json = 'json';
                case Xml = 'xml';
            }
            PHP);

        // named like the attribute the generated command imports
        $runner->writeFile('src/Enum/Option.php', <<<'PHP'
            <?php

            namespace App\Enum;

            enum Option: int
            {
                case On = 1;
                case Off = 0;
            }
            PHP);
    }

    private static function runCommandTest(MakerTestRunner $runner, string $filename): void
    {
        $runner->copy(
            'make-command/tests/'.$filename,
            'tests/GeneratedCommandTest.php'
        );

        $runner->runTests();
    }
}
