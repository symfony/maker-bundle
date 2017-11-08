<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Command;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Validator\Validation;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeValidatorCommand extends AbstractCommand
{
    protected static $defaultName = 'make:validator';

    public function configure()
    {
        $this
            ->setDescription('Creates a new validator and constraint class')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the validator class (e.g. <fg=yellow>EnabledValidator</>).')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeValidator.txt'))
        ;
    }

    protected function getParameters(): array
    {
        $validatorClassName = Str::asClassName($this->input->getArgument('name'), 'Validator');
        Validator::validateClassName($validatorClassName);
        $constraintClassName = Str::removeSuffix($validatorClassName, 'Validator');

        return [
            'validator_class_name' => $validatorClassName,
            'constraint_class_name' => $constraintClassName,
        ];
    }

    protected function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/validator/Validator.php.txt' => 'src/Validator/Constraints/'.$params['validator_class_name'].'.php',
            __DIR__.'/../Resources/skeleton/validator/Constraint.php.txt' => 'src/Validator/Constraints/'.$params['constraint_class_name'].'.php',
        ];
    }

    protected function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        $io->text([
            'Next: Open your new constraint & validators and add your logic.',
            'Find the documentation at <fg=yellow>http://symfony.com/doc/current/validation/custom_constraint.html</>'
        ]);
    }

    protected function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Validation::class,
            'validator'
        );
    }
}
