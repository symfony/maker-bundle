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
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassData;
use Symfony\Bundle\MakerBundle\Validator\TargetEnum;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Validation;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeValidator extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:validator';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a new validator and constraint class';
    }

    /** @return void */
    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the validator class (e.g. <fg=yellow>EnabledValidator</>)')
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'The target of the constraint class')
            ->setHelp($this->getHelpFileContents('MakeValidator.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $target = $input->getOption('target');
        $supportedTargets = TargetEnum::values();

        if (!$target || \in_array($target, $supportedTargets)) {
            return;
        }

        $helper = new QuestionHelper();
        $question = new ChoiceQuestion(
            'Target type',
            $supportedTargets
        );

        $target = $helper->ask($input, $io->getOutput(), $question);
        $input->setOption('target', $target);
    }

    /** @return void */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $validatorClassData = ClassData::create(
            class: \sprintf('Validator\\%s', $input->getArgument('name')),
            suffix: 'Validator',
            extendsClass: ConstraintValidator::class,
            useStatements: [
                Constraint::class,
            ],
        );

        $constraintDataClass = ClassData::create(
            class: \sprintf('Validator\\%s', Str::removeSuffix($validatorClassData->getClassName(), 'Validator')),
            extendsClass: Constraint::class,
        );

        $generator->generateClassFromClassData(
            $validatorClassData,
            'validator/Validator.tpl.php',
            [
                'constraint_class_name' => $constraintDataClass->getClassName(),
            ]
        );

        $generator->generateClassFromClassData(
            $constraintDataClass,
            'validator/Constraint.tpl.php',
            [
                'target' => $input->getOption('target'),
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new constraint & validators and add your logic.',
            'Find the documentation at <fg=yellow>http://symfony.com/doc/current/validation/custom_constraint.html</>',
        ]);
    }

    /** @return void */
    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Validation::class,
            'validator'
        );
    }
}
