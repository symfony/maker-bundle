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
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

/**
 * @author Stephanot Zafindratafa
 *
 * @internal
 */
final class MakeConstant extends AbstractMaker
{
    private const VALID_TYPES = ['string', 'int', 'float', 'bool', 'array'];
    private const VALID_VISIBILITIES = ['public', 'protected', 'private'];

    public function __construct(
        private FileManager $fileManager,
        private Generator $generator,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:constant';
    }

    public static function getCommandDescription(): string
    {
        return 'Add a constant to an existing PHP class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('class', InputArgument::OPTIONAL, 'The class where the constant will be added (e.g. <fg=yellow>Order</> or <fg=yellow>App\Entity\Order</>)')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the constant, in <fg=yellow>UPPER_SNAKE_CASE</> (e.g. <fg=yellow>STATUS_PENDING</>)')
            ->addArgument('value', InputArgument::OPTIONAL, 'The value of the constant (e.g. <fg=yellow>pending</>)')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'The type of the constant: string, int, float, bool or array (guessed from the value when not provided)')
            ->addOption('visibility', null, InputOption::VALUE_REQUIRED, 'The visibility of the constant: public, protected or private', 'public')
            ->addOption('comment', 'c', InputOption::VALUE_REQUIRED, 'An optional PHPDoc description for the constant')
            ->addOption('final', null, InputOption::VALUE_NONE, 'Mark the constant as final (requires PHP 8.1+)')
            ->setHelp($this->getHelpFileContents('MakeConstant.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (null === $input->getArgument('class')) {
            $argument = $command->getDefinition()->getArgument('class');

            $question = new Question($argument->getDescription());
            $question->setAutocompleterValues($this->getExistingClassesForAutocomplete());
            $question->setValidator($this->resolveClassName(...));
            $question->setMaxAttempts(3);

            $input->setArgument('class', $io->askQuestion($question));
        }

        if (null === $input->getArgument('name')) {
            $name = $io->ask(
                $command->getDefinition()->getArgument('name')->getDescription(),
                null,
                Validator::validateConstantName(...)
            );

            $input->setArgument('name', $name);
        }

        if (null === $input->getArgument('value')) {
            $value = $io->ask(
                $command->getDefinition()->getArgument('value')->getDescription(),
                null,
                Validator::notBlank(...)
            );

            $input->setArgument('value', $value);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $className = $this->resolveClassName($input->getArgument('class'));
        $constantName = Validator::validateConstantName($input->getArgument('name'));

        $visibility = $input->getOption('visibility');
        if (!\in_array($visibility, self::VALID_VISIBILITIES, true)) {
            throw new RuntimeCommandException(\sprintf('Invalid visibility "%s": expected one of "public", "protected" or "private".', $visibility));
        }

        $reflectionClass = new \ReflectionClass($className);

        if ($reflectionClass->isInterface() || $reflectionClass->isEnum() || $reflectionClass->isTrait()) {
            throw new RuntimeCommandException(\sprintf('"%s" is not a regular class: constants can only be added to classes.', $className));
        }

        $path = $reflectionClass->getFileName();
        if (!$path) {
            throw new RuntimeCommandException(\sprintf('Cannot determine the file where class "%s" is defined.', $className));
        }

        [$value, $type] = $this->resolveValueAndType($input->getArgument('value'), $input->getOption('type'));

        $manipulator = new ClassSourceManipulator(
            sourceCode: $this->fileManager->getFileContents($path),
            overwrite: false,
        );
        $manipulator->setIo($io);

        if ($manipulator->constantExists($constantName)) {
            throw new RuntimeCommandException(\sprintf('A constant named "%s" already exists in class "%s".', $constantName, $className));
        }

        $manipulator->addConstant(
            name: $constantName,
            value: $value,
            // only add a native type declaration when the user explicitly asked for one:
            // typed class constants require PHP 8.3+ and we don't know the target project's PHP version
            type: $input->getOption('type') ? $type : null,
            visibility: $visibility,
            comments: $input->getOption('comment') ? [$input->getOption('comment')] : [],
            final: $input->getOption('final'),
        );

        $generator->dumpFile($path, $manipulator->getSourceCode());
        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text(\sprintf(
            'Next: Open your <info>%s</info> class and use the new <info>%s::%s</info> constant.',
            $reflectionClass->getShortName(),
            $reflectionClass->getShortName(),
            $constantName
        ));
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    /**
     * @return array{0: string|int|float|bool|array, 1: string}
     */
    private function resolveValueAndType(string $rawValue, ?string $requestedType): array
    {
        $type = $requestedType ? strtolower($requestedType) : $this->guessType($rawValue);

        if (!\in_array($type, self::VALID_TYPES, true)) {
            throw new RuntimeCommandException(\sprintf('Invalid type "%s": expected one of "string", "int", "float", "bool" or "array".', $type));
        }

        $value = match ($type) {
            'int' => $this->castToInt($rawValue),
            'float' => $this->castToFloat($rawValue),
            'bool' => $this->castToBool($rawValue),
            'array' => $this->castToArray($rawValue),
            default => $rawValue,
        };

        return [$value, $type];
    }

    private function guessType(string $rawValue): string
    {
        if (\in_array(strtolower($rawValue), ['true', 'false'], true)) {
            return 'bool';
        }

        if (preg_match('/^-?\d+$/', $rawValue)) {
            return 'int';
        }

        if (is_numeric($rawValue)) {
            return 'float';
        }

        return 'string';
    }

    private function castToInt(string $rawValue): int
    {
        if (!preg_match('/^-?\d+$/', $rawValue)) {
            throw new RuntimeCommandException(\sprintf('"%s" is not a valid integer value.', $rawValue));
        }

        return (int) $rawValue;
    }

    private function castToFloat(string $rawValue): float
    {
        if (!is_numeric($rawValue)) {
            throw new RuntimeCommandException(\sprintf('"%s" is not a valid float value.', $rawValue));
        }

        return (float) $rawValue;
    }

    private function castToBool(string $rawValue): bool
    {
        return match (strtolower($rawValue)) {
            'true', '1' => true,
            'false', '0' => false,
            default => throw new RuntimeCommandException(\sprintf('"%s" is not a valid boolean value: use "true" or "false".', $rawValue)),
        };
    }

    /**
     * @return array<int|string, mixed>
     */
    private function castToArray(string $rawValue): array
    {
        $decoded = json_decode($rawValue, true);

        if (\JSON_ERROR_NONE === json_last_error() && \is_array($decoded)) {
            return $decoded;
        }

        return array_map('trim', explode(',', $rawValue));
    }

    /**
     * Resolves the user-provided class name (short name or FQCN) to an
     * existing, fully-qualified class name.
     */
    private function resolveClassName(string $rawClassName): string
    {
        $rawClassName = ltrim(trim($rawClassName), '\\');

        if (class_exists($rawClassName)) {
            return $rawClassName;
        }

        $rootNamespace = trim($this->generator->getRootNamespace(), '\\');

        if (class_exists($rootNamespace.'\\'.$rawClassName)) {
            return $rootNamespace.'\\'.$rawClassName;
        }

        $matches = [];
        foreach ($this->getExistingClassesForAutocomplete() as $existingClass) {
            if (Str::getShortClassName($existingClass) === $rawClassName) {
                $matches[] = $existingClass;
            }
        }

        if (1 === \count($matches)) {
            return $matches[0];
        }

        if (\count($matches) > 1) {
            throw new RuntimeCommandException(\sprintf('Several classes named "%s" were found, please provide the fully-qualified class name: "%s".', $rawClassName, implode('", "', $matches)));
        }

        throw new RuntimeCommandException(\sprintf('Class "%s" doesn\'t exist. This command only adds a constant to an existing class: create the class first.', $rawClassName));
    }

    /**
     * @return string[]
     */
    private function getExistingClassesForAutocomplete(): array
    {
        $rootNamespace = trim($this->generator->getRootNamespace(), '\\');
        $srcDirectory = $this->fileManager->getRootDirectory().'/src';

        $finder = new Finder();

        try {
            $finder->files()->in($srcDirectory)->name('*.php');
        } catch (DirectoryNotFoundException) {
            return [];
        }

        $classes = [];
        foreach ($finder as $file) {
            $relativePath = str_replace([$srcDirectory.'/', '.php'], ['', ''], str_replace('\\', '/', $file->getRealPath()));
            $className = $rootNamespace.'\\'.str_replace('/', '\\', $relativePath);

            if (class_exists($className)) {
                $classes[] = $className;
            }
        }

        sort($classes);

        return $classes;
    }
}
