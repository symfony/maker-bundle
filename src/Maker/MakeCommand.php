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
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\EnumHelper;
use Symfony\Bundle\MakerBundle\Util\PhpCompatUtil;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeCommand extends AbstractMaker
{
    private const PARAMETER_TYPES = ['string', 'bool', 'int', 'float', 'array'];

    private const EMPTY_VALUES = [
        'string' => "''",
        'bool' => 'false',
        'int' => '0',
        'float' => '0.0',
        'array' => '[]',
    ];

    /**
     * @var list<array{name: string, type: string, description: string, required: bool}>
     */
    private array $arguments = [];

    /**
     * @var list<array{name: string, type: string, description: string, required: bool}>
     */
    private array $options = [];

    public function __construct(
        ?PhpCompatUtil $phpCompatUtil = null,
        private ?FileManager $fileManager = null,
    ) {
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
            ->addOption('argument', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Add an argument without being asked for it: <fg=yellow>name[?][:type]</> (e.g. <fg=yellow>recipient:string</>). Repeat the option for each argument')
            ->addOption('option', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Add an option without being asked for it: <fg=yellow>name[:type]</> (e.g. <fg=yellow>dry-run:bool</>). Repeat it for each option')
            ->setHelp($this->getHelpFileContents('MakeCommand.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        // they were passed on the command line, asking for more would mix both ways of declaring them
        if ($input->getOption('argument') || $input->getOption('option')) {
            return;
        }

        $io->text('The command is generated with an <fg=yellow>__invoke()</> method, whose parameters are the arguments and options of the command.');

        $this->arguments = $this->askForParameters($io, 'argument');
        $this->options = $this->askForParameters($io, 'option', $this->arguments);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $commandName = trim($input->getArgument('name'));

        $commandName = Validator::validatePhpStringLiteral($commandName, \sprintf('The command name "%s" cannot contain quotes or backslashes.', $commandName));

        $commandNameHasAppPrefix = str_starts_with($commandName, 'app:');

        $commandClassNameDetails = $generator->createClassNameDetails(
            $commandNameHasAppPrefix ? substr($commandName, 4) : $commandName,
            'Command\\',
            'Command',
            \sprintf('The "%s" command name is not valid because it would be implemented by "%s" class, which is not valid as a PHP class name (it must start with a letter or underscore, followed by any number of letters, numbers, or underscores).', $commandName, Str::asClassName($commandName, 'Command'))
        );

        $argumentDefinitions = $input->getOption('argument');
        $optionDefinitions = $input->getOption('option');

        if ($argumentDefinitions || $optionDefinitions) {
            [$arguments, $options] = self::parseDefinitions($argumentDefinitions, $optionDefinitions, $generator);
        } else {
            $arguments = $this->arguments;
            $options = $this->options;
        }

        if (!$arguments && !$options) {
            // Nothing was asked, either because of --no-interaction or because the user went straight
            // through the questions. The sample parameters are kept: they show both attributes at work.
            $arguments = [['name' => 'arg', 'type' => 'string', 'description' => 'Argument description', 'required' => false]];
            $options = [['name' => 'enable', 'type' => 'bool', 'description' => 'Option description', 'required' => false]];
        }

        $useStatements = new UseStatementGenerator([
            Command::class,
            SymfonyStyle::class,
            AsCommand::class,
        ]);

        if ($arguments) {
            $useStatements->addUseStatement(Argument::class);
        }

        if ($options) {
            $useStatements->addUseStatement(Option::class);
        }

        foreach ([...$arguments, ...$options] as $parameter) {
            // an enum named like one of the imports above, "Option" say, is imported under an alias
            if (!\in_array($parameter['type'], self::PARAMETER_TYPES, true) && !$useStatements->hasUseStatement($parameter['type'])) {
                $useStatements->addUseStatement($parameter['type'], 'Enum');
            }
        }

        $generator->generateClass(
            $commandClassNameDetails->getFullName(),
            'command/Command.tpl.php',
            [
                'use_statements' => $useStatements,
                'command_name' => $commandName,
                'command_parameters' => [
                    ...array_map(static fn (array $argument): string => self::buildParameter('Argument', $argument, $useStatements), $arguments),
                    ...array_map(static fn (array $option): string => self::buildParameter('Option', $option, $useStatements), $options),
                ],
                'command_notes' => array_map(self::buildNote(...), [...$arguments, ...$options]),
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
     * @param list<array{name: string, type: string, description: string, required: bool}> $declared their names are taken
     *
     * @return list<array{name: string, type: string, description: string, required: bool}>
     */
    private function askForParameters(ConsoleStyle $io, string $kind, array $declared = []): array
    {
        $parameters = [];

        while (true) {
            $io->writeln('');
            $name = $io->ask(\sprintf('New %s name (press <return> to stop adding %ss)', $kind, $kind));

            if (null === $name || '' === trim($name)) {
                return $parameters;
            }

            $name = self::normalizeName($name);

            if (null !== $error = self::validateName($name, [...$declared, ...$parameters])) {
                $io->error($error);

                continue;
            }

            $type = $io->choice('Type', [...self::PARAMETER_TYPES, 'enum'], 'option' === $kind ? 'bool' : 'string');

            if ('enum' === $type) {
                $type = $io->askQuestion($this->createEnumQuestion());
            }

            $parameters[] = [
                'name' => $name,
                'type' => $type,
                'description' => $io->ask('Description', Str::asHumanWords($name)),
                // An option is never required: not passing it is what its default value stands for.
                // Neither is an argument that follows an optional one, which PHP and the console
                // component both refuse, so the question is only worth asking while none was added.
                'required' => 'argument' === $kind
                    && !\in_array(false, array_column($parameters, 'required'), true)
                    && $io->confirm('Is it required?', false),
            ];

            if ('argument' === $kind && 'array' === $type) {
                // the console component refuses any argument after it
                $io->text('An array argument takes every remaining value, so it has to be the last one.');

                return $parameters;
            }
        }
    }

    private function createEnumQuestion(): Question
    {
        $question = new Question('Backed enum class');
        $question->setValidator(Validator::classIsBackedEnum(...));

        if ($this->fileManager) {
            $question->setAutocompleterValues((new EnumHelper($this->fileManager->getRootDirectory().'/src', 'App'))->getAllEnums());
        }

        return $question;
    }

    /**
     * @param string[] $argumentDefinitions
     * @param string[] $optionDefinitions
     *
     * @return array{list<array{name: string, type: string, description: string, required: bool}>, list<array{name: string, type: string, description: string, required: bool}>}
     */
    private static function parseDefinitions(array $argumentDefinitions, array $optionDefinitions, Generator $generator): array
    {
        $arguments = [];

        foreach ($argumentDefinitions as $definition) {
            $arguments[] = self::parseDefinition('argument', $definition, $arguments, $generator);
        }

        $options = [];

        foreach ($optionDefinitions as $definition) {
            $options[] = self::parseDefinition('option', $definition, [...$arguments, ...$options], $generator);
        }

        return [$arguments, $options];
    }

    /**
     * Parses <name>[?][:<type>], where "?" makes an argument optional the way it does a key of a PHP array shape.
     *
     * @param list<array{name: string, type: string, description: string, required: bool}> $declared parsed so far, arguments first
     *
     * @return array{name: string, type: string, description: string, required: bool}
     */
    private static function parseDefinition(string $kind, string $definition, array $declared, Generator $generator): array
    {
        $parts = explode(':', trim($definition), 2);
        $name = $parts[0];

        if ($optional = str_ends_with($name, '?')) {
            $name = substr($name, 0, -1);
        }

        $name = self::normalizeName($name);
        $type = ($parts[1] ?? '') ?: ('option' === $kind ? 'bool' : 'string');

        if ('' === $name) {
            throw new RuntimeCommandException(\sprintf('The definition "%s" is missing a name.', $definition));
        }

        if (null !== $error = self::validateName($name, $declared)) {
            throw new RuntimeCommandException($error);
        }

        $type = self::resolveType($type, $definition, $generator);

        if ('option' === $kind && $optional) {
            throw new RuntimeCommandException(\sprintf('The option "%s" cannot have a "?", an option is always optional.', $definition));
        }

        if ('argument' === $kind) {
            // the console component refuses both, and would only say so once the generated command runs
            foreach ($declared as $argument) {
                if ('array' === $argument['type']) {
                    throw new RuntimeCommandException(\sprintf('No argument can follow the array argument "%s", which takes every remaining value.', $argument['name']));
                }

                if (!$optional && !$argument['required']) {
                    throw new RuntimeCommandException(\sprintf('The argument "%s" cannot be required after the optional "%s", add a "?" to its name.', $definition, $argument['name']));
                }
            }
        }

        return [
            'name' => $name,
            'type' => $type,
            'description' => Str::asHumanWords($name),
            'required' => 'argument' === $kind && !$optional,
        ];
    }

    /**
     * @return string one of the parameter types, or the class of a backed enum
     */
    private static function resolveType(string $type, string $definition, Generator $generator): string
    {
        if (\in_array($type, self::PARAMETER_TYPES, true)) {
            return $type;
        }

        if (str_contains($type, '\\')) {
            $enums = enum_exists($type) ? [ltrim($type, '\\')] : [];
        } else {
            // a short name spares the caller from escaping backslashes in the shell
            $enumHelper = new EnumHelper($generator->getRootDirectory().'/src', rtrim($generator->getRootNamespace(), '\\'));
            $enums = array_values(array_filter($enumHelper->getAllEnums(), static fn (string $enum): bool => Str::getShortClassName($enum) === $type));
        }

        if (!$enums) {
            throw new RuntimeCommandException(\sprintf('Invalid type "%s" in "%s", use one of "%s" or the name of a backed enum.', $type, $definition, implode('", "', self::PARAMETER_TYPES)));
        }

        if (1 < \count($enums)) {
            throw new RuntimeCommandException(\sprintf('The enum "%s" in "%s" is ambiguous, use its full class name: "%s".', $type, $definition, implode('", "', $enums)));
        }

        if (!is_subclass_of($enums[0], \BackedEnum::class)) {
            throw new RuntimeCommandException(\sprintf('The enum "%s" in "%s" is not backed, which the console component needs to read it from the input.', $enums[0], $definition));
        }

        return $enums[0];
    }

    private static function normalizeName(string $name): string
    {
        // the name is typed the way it reads on the command line, but it becomes a parameter
        return Str::asLowerCamelCase(str_replace('-', '_', trim($name)));
    }

    /**
     * @param list<array{name: string, type: string, description: string, required: bool}> $declared
     */
    private static function validateName(string $name, array $declared): ?string
    {
        if (!Str::isValidPhpVariableName($name)) {
            return \sprintf('"%s" is not a valid PHP variable name.', $name);
        }

        // $io is the first parameter of the generated method
        if ('io' === $name || \in_array($name, array_column($declared, 'name'), true)) {
            return \sprintf('__invoke() already has a "$%s" parameter.', $name);
        }

        return null;
    }

    /**
     * @param array{name: string, type: string, description: string, required: bool} $parameter
     */
    private static function buildParameter(string $attribute, array $parameter, UseStatementGenerator $useStatements): string
    {
        $isEnum = !\in_array($parameter['type'], self::PARAMETER_TYPES, true);

        return \sprintf(
            '#[%s(\'%s\')] %s%s $%s%s',
            $attribute,
            addcslashes($parameter['description'], "'\\"),
            // an optional enum has no empty case to default to
            $isEnum && !$parameter['required'] ? '?' : '',
            $isEnum ? $useStatements->getShortName($parameter['type']) : $parameter['type'],
            $parameter['name'],
            $parameter['required'] ? '' : ' = '.(self::EMPTY_VALUES[$parameter['type']] ?? 'null'),
        );
    }

    /**
     * @param array{name: string, type: string, description: string, required: bool} $parameter
     */
    private static function buildNote(array $parameter): string
    {
        return \sprintf('$io->note(sprintf(\'The value of $%1$s is: %%s\', var_export($%1$s, true)));', $parameter['name']);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            Command::class,
            'console'
        );
    }
}
