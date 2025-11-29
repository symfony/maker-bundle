<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\PhpCompatUtil;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeCommand extends AbstractMaker
{
    public function __construct(private ?PhpCompatUtil $phpCompatUtil = null)
    {
        if (null !== $phpCompatUtil) {
            @trigger_deprecation(
                'symfony/maker-bundle',
                '1.55.0',
                \sprintf('Initializing MakeCommand while providing an instance of "%s" is deprecated. The $phpCompatUtil param will be removed in a future version.', PhpCompatUtil::class),
            );
        }
    }

    public static function getCommandName(): string
    {
        return 'make:command';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a new console command class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, \sprintf('Choose a command name (e.g. <fg=yellow>app:%s</>)', Str::asCommand(Str::getRandomTerm())))
            ->setHelp($this->getHelpFileContents('MakeCommand.txt'));

            $command->addOption('invokable', 'i', InputOption::VALUE_NEGATABLE, 'Use this option to create an invokable command', default: $this->supportsInvokableCommand());
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $commandName = trim($input->getArgument('name'));
        $commandNameHasAppPrefix = str_starts_with($commandName, 'app:');

        $commandClassNameDetails = $generator->createClassNameDetails(
            $commandNameHasAppPrefix ? substr($commandName, 4) : $commandName,
            'Command\\',
            'Command',
            \sprintf('The "%s" command name is not valid because it would be implemented by "%s" class, which is not valid as a PHP class name (it must start with a letter or underscore, followed by any number of letters, numbers, or underscores).', $commandName, Str::asClassName($commandName, 'Command'))
        );

        $this->supportsInvokableCommand() && $input->getOption('invokable') ?
            $this->generateInvokableCommand($commandName, $commandClassNameDetails, $io, $generator) :
            $this->generateInheritanceCommand($commandName, $commandClassNameDetails, $io, $generator);
    }

    private function generateInheritanceCommand(string $commandName, ClassNameDetails $commandClassNameDetails, ConsoleStyle $io, Generator $generator): void
    {
        $useStatements = new UseStatementGenerator([
            Command::class,
            InputArgument::class,
            InputInterface::class,
            InputOption::class,
            OutputInterface::class,
            SymfonyStyle::class,
            AsCommand::class,
        ]);

        $generator->generateClass(
            $commandClassNameDetails->getFullName(),
            'command/InheritanceCommand.tpl.php',
            [
                'use_statements' => $useStatements,
                'command_name' => $commandName,
                'set_description' => !class_exists(LazyCommand::class),
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $io->text([
            'Next: open your new command class and customize it!',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/console.html</>',
        ]);
    }

    private function generateInvokableCommand(string $commandName, ClassNameDetails $commandClassNameDetails, ConsoleStyle $io, Generator $generator): void
    {
        if (class_exists($commandClassNameDetails->getFullName())) {
            $io->error('This command already exists.');

            return;
        }

        $description = $io->ask('What is the command description?');
        if (false === \is_string($description)) {
            $description = (string) $description;
        }

        $arguments = $this->askForArguments($io);
        $options = $this->askForOptions($io);

        $useStatements = new UseStatementGenerator([
            AsCommand::class,
            Argument::class,
            Command::class,
            Option::class,
            SymfonyStyle::class,
        ]);

        $generator->generateClass(
            $commandClassNameDetails->getFullName(),
            'command/Command.tpl.php',
            [
                'use_statements' => $useStatements,
                'command_name' => $commandName,
                'command_description' => $description,
                'command_parameters' => $this->mergeAndSortParameters($arguments, $options),
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $io->text([
            'Next: open your new command class and customize it!',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/console.html</>',
        ]);
    }

    /**
     * @param array<int, array{name: string, type: string, description: string|null, default: mixed, nullable: bool}>        $arguments
     * @param array<int, array{name: string, shortcut: string|null, type: string, description: string|null, default: mixed}> $options
     *
     * @return array<int, array{name: string, type: string, description: string|null, default: mixed, nullable?: bool, shortcut?: string|null, param_type: string}>
     */
    private function mergeAndSortParameters(array $arguments, array $options): array
    {
        // Merge arguments and options, marking each with its type
        $parameters = [];
        foreach ($arguments as $arg) {
            $parameters[] = array_merge($arg, ['param_type' => 'argument']);
        }
        foreach ($options as $opt) {
            $parameters[] = array_merge($opt, ['param_type' => 'option']);
        }

        // Sort parameters: required parameters (no defaults) must come before optional ones (with defaults)
        usort($parameters, function ($a, $b) {
            $aHasDefault = $this->parameterHasDefault($a);
            $bHasDefault = $this->parameterHasDefault($b);

            if ($aHasDefault === $bHasDefault) {
                return 0; // Keep original order within same group (required with required, optional with optional)
            }

            // Required (no default) comes before optional (has default)
            return $aHasDefault ? 1 : -1;
        });

        return $parameters;
    }

    /** @param array{default: mixed, param_type: string, nullable?: bool} $param */
    private function parameterHasDefault(array $param): bool
    {
        if ('argument' === $param['param_type']) {
            // Arguments have defaults if explicitly set or if nullable
            return null !== $param['default'] || ($param['nullable'] ?? false);
        }

        // Options always have defaults
        return true;
    }

    /**
     * @return array<int, array{name: string, type: string, description: string|null, default: mixed, nullable: bool}>
     */
    private function askForArguments(ConsoleStyle $io): array
    {
        $io->writeln('Now, let\'s add some arguments.');

        $arguments = [];
        $isFirst = true;

        while (true) {
            $io->writeln('');

            if ($isFirst) {
                $questionText = 'Argument name? (press <return> to stop adding arguments)';
            } else {
                $questionText = 'Add another argument? Enter the argument name (or press <return> to stop adding arguments)';
            }

            $name = $io->ask($questionText, null, function ($name) use ($arguments) {
                // allow it to be empty
                if (!$name) {
                    return $name;
                }

                foreach ($arguments as $arg) {
                    if ($arg['name'] === $name) {
                        throw new \InvalidArgumentException(\sprintf('The "%s" argument already exists.', $name));
                    }
                }

                return Str::asLowerCamelCase(strtr($name, ['-' => ' ']));
            });

            if (!$name) {
                break;
            }

            $isFirst = false;

            $type = $io->choice(
                'What is the argument type?',
                ['string', 'int', 'float', 'bool', 'array'],
                'string'
            );

            $arguments[] = [
                'name' => $name,
                'type' => $type,
                'description' => $this->askForParameterDescription($io),
                'default' => $this->askForDefaults($io, $type),
                'nullable' => $io->confirm('Is this argument nullable?', false),
            ];
        }

        return $arguments;
    }

    /**
     * @return array<int, array{name: string, shortcut: string|null, type: string, description: string|null, default: mixed}>
     */
    private function askForOptions(ConsoleStyle $io): array
    {
        $io->writeln('Now, let\'s add some options.');

        $options = [];
        $isFirst = true;

        while (true) {
            $io->writeln('');

            if ($isFirst) {
                $questionText = 'What is the option name?';
            } else {
                $questionText = 'What is the next option name?';
            }

            $name = $io->ask($questionText, null, function ($name) use ($options) {
                // allow it to be empty
                if (!$name) {
                    return $name;
                }

                foreach ($options as $opt) {
                    if ($opt['name'] === $name) {
                        throw new \InvalidArgumentException(\sprintf('The "%s" option already exists.', $name));
                    }
                }

                return Str::asLowerCamelCase(strtr($name, ['-' => ' ']));
            });

            if (!$name) {
                break;
            }

            $isFirst = false;

            $shortcut = $io->ask('What is the option shortcut?');
            if (!\is_string($shortcut) && null !== $shortcut) {
                $shortcut = (string) $shortcut;
            }

            $type = $io->choice(
                'What is the option type?',
                ['bool', 'string', 'int', 'float'],
                'bool'
            );

            $options[] = [
                'name' => Str::asLowerCamelCase($name),
                'shortcut' => $shortcut,
                'type' => $type,
                'description' => $this->askForParameterDescription($io),
                'default' => $this->askForDefaults($io, $type, true),
            ];
        }

        return $options;
    }

    private function askForParameterDescription(ConsoleStyle $io): ?string
    {
        $description = $io->ask('What is the description?', null);
        if (null !== $description && !\is_string($description)) {
            $description = (string) $description;
        }

        if (false === \is_scalar($description)) {
            $description = null;
        }

        return $description;
    }

    private function askForDefaults(ConsoleStyle $io, string $type, bool $force = false): mixed
    {
        if (false === $force) {
            $hasDefault = $io->confirm('Does it have a default value?', false);

            if (false === $hasDefault) {
                return null;
            }
        }

        if ('bool' === $type) {
            return $io->confirm('What is the default value?', false);
        } elseif ('int' === $type) {
            return (int) $io->ask('What is the default value?', '0');
        } elseif ('float' === $type) {
            return (float) $io->ask('What is the default value?', '0.0');
        } elseif ('array' === $type) {
            $defaultValue = $io->ask('What is the default value?', '[]');

            return '[]' === $defaultValue ? [] : $defaultValue;
        } else {
            $default = $io->ask('What is the default value?', '');
            if (true === \is_scalar($default)) {
                return (string) $default;
            }

            return null;
        }
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            Command::class,
            'console'
        );
    }

    private function supportsInvokableCommand(): bool
    {
        return Kernel::VERSION_ID >= 70300;
    }
}
