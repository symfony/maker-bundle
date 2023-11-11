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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;

/**
 * @author Abdelilah Jabri <jbrabdelilah@gmail.com>
 *
 * @internal
 */
final class MakeStimulusController extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:stimulus-controller';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a new Stimulus controller';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the Stimulus controller (e.g. <fg=yellow>hello</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeStimulusController.txt'));
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $command->addArgument('extension', InputArgument::OPTIONAL);
        $command->addArgument('targets', InputArgument::OPTIONAL, '', []);
        $command->addArgument('values', InputArgument::OPTIONAL, '', []);

        $chosenExtension = $io->choice(
            'Language (<fg=yellow>JavaScript</> or <fg=yellow>TypeScript</>)',
            [
                'js' => 'JavaScript',
                'ts' => 'TypeScript',
            ]
        );

        $input->setArgument('extension', $chosenExtension);

        if ($io->confirm('Do you want to include targets?')) {
            $targets = [];
            $isFirstTarget = true;

            while (true) {
                $newTarget = $this->askForNextTarget($io, $targets, $isFirstTarget);
                $isFirstTarget = false;

                if (null === $newTarget) {
                    break;
                }

                $targets[] = $newTarget;
            }

            $input->setArgument('targets', $targets);
        }

        if ($io->confirm('Do you want to include values?')) {
            $values = [];
            $isFirstValue = true;
            while (true) {
                $newValue = $this->askForNextValue($io, $values, $isFirstValue);
                $isFirstValue = false;

                if (null === $newValue) {
                    break;
                }

                $values[$newValue['name']] = $newValue;
            }

            $input->setArgument('values', $values);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $controllerName = Str::asSnakeCase($input->getArgument('name'));
        $chosenExtension = $input->getArgument('extension');
        $targets = $input->getArgument('targets');
        $values = $input->getArgument('values');

        $targets = empty($targets) ? $targets : sprintf("['%s']", implode("', '", $targets));

        $fileName = sprintf('%s_controller.%s', $controllerName, $chosenExtension);
        $filePath = sprintf('assets/controllers/%s', $fileName);

        $generator->generateFile(
            $filePath,
            'stimulus/Controller.tpl.php',
            [
                'targets' => $targets,
                'values' => $values,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next:',
            sprintf('- Open <info>%s</info> and add the code you need', $filePath),
            'Find the documentation at <fg=yellow>https://github.com/symfony/stimulus-bridge</>',
        ]);
    }

    private function askForNextTarget(ConsoleStyle $io, array $targets, bool $isFirstTarget): ?string
    {
        $questionText = 'New target name (press <return> to stop adding targets)';

        if (!$isFirstTarget) {
            $questionText = 'Add another target? Enter the target name (or press <return> to stop adding targets)';
        }

        $targetName = $io->ask($questionText, null, function (?string $name) use ($targets) {
            if (\in_array($name, $targets)) {
                throw new \InvalidArgumentException(sprintf('The "%s" target already exists.', $name));
            }

            return $name;
        });

        return !$targetName ? null : $targetName;
    }

    private function askForNextValue(ConsoleStyle $io, array $values, bool $isFirstValue): ?array
    {
        $questionText = 'New value name (press <return> to stop adding values)';

        if (!$isFirstValue) {
            $questionText = 'Add another value? Enter the value name (or press <return> to stop adding values)';
        }

        $valueName = $io->ask($questionText, null, function ($name) use ($values) {
            if (\array_key_exists($name, $values)) {
                throw new \InvalidArgumentException(sprintf('The "%s" value already exists.', $name));
            }

            return $name;
        });

        if (!$valueName) {
            return null;
        }

        $defaultType = 'String';
        // try to guess the type by the value name prefix/suffix
        // convert to snake case for simplicity
        $snakeCasedField = Str::asSnakeCase($valueName);

        if ('_id' === substr($snakeCasedField, -3)) {
            $defaultType = 'Number';
        } elseif (str_starts_with($snakeCasedField, 'is_')) {
            $defaultType = 'Boolean';
        } elseif (str_starts_with($snakeCasedField, 'has_')) {
            $defaultType = 'Boolean';
        }

        $type = null;
        $types = $this->getValuesTypes();

        while (null === $type) {
            $question = new Question('Value type (enter <comment>?</comment> to see all types)', $defaultType);
            $question->setAutocompleterValues($types);
            $type = $io->askQuestion($question);

            if ('?' === $type) {
                $this->printAvailableTypes($io);
                $io->writeln('');

                $type = null;
            } elseif (!\in_array($type, $types)) {
                $this->printAvailableTypes($io);
                $io->error(sprintf('Invalid type "%s".', $type));
                $io->writeln('');

                $type = null;
            }
        }

        return ['name' => $valueName, 'type' => $type];
    }

    private function printAvailableTypes(ConsoleStyle $io): void
    {
        foreach ($this->getValuesTypes() as $type) {
            $io->writeln(sprintf('<info>%s</info>', $type));
        }
    }

    private function getValuesTypes(): array
    {
        return [
            'Array',
            'Boolean',
            'Number',
            'Object',
            'String',
        ];
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        // lower than 8.1, allow WebpackEncoreBundle
        if (\PHP_VERSION_ID < 80100) {
            $dependencies->addClassDependency(
                WebpackEncoreBundle::class,
                'symfony/webpack-encore-bundle'
            );

            return;
        }

        // else: encourage StimulusBundle by requiring it
        $dependencies->addClassDependency(
            StimulusBundle::class,
            'symfony/stimulus-bundle'
        );
    }
}
