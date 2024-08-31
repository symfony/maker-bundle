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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeValidator.txt'))
        ;
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

        $generator->generateClass(
            $validatorClassData->getFullClassName(),
            'validator/Validator.tpl.php',
            [
                'class_data' => $validatorClassData,
                'constraint_class_name' => $constraintDataClass->getClassName(),
            ]
        );

        $generator->generateClass(
            $constraintDataClass->getFullClassName(),
            'validator/Constraint.tpl.php',
            [
                'class_data' => $constraintDataClass,
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
