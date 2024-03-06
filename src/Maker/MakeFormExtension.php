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

use ReflectionClass;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Form\AbstractTypeExtension;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Bechir Ba <bechiirr71@gmail.com>
 */
class MakeFormExtension extends AbstractMaker
{
    public function __construct(private array $types = [])
    {
    }

    public static function getCommandName(): string
    {
        return 'make:form-extension';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new form extension class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'Choose a name for your form extension class (e.g. <fg=yellow>ImageTypeExtension</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeFormExtension.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        $command->addArgument('extended_type', InputArgument::OPTIONAL, 'The class name of the extended form type (e.g. <fg=yellow>FileType</>)');

        $question = new Question(sprintf(' <fg=green>%s</>', $command->getDefinition()->getArgument('extended_type')->getDescription()));

        $question->setAutocompleterValues($this->types);
        $question->setValidator(function ($extendedType) {
            Validator::notBlank($extendedType);

            if (!isset($this->types[$extendedType])) {
                Validator::classExists($extendedType);
            }

            return $extendedType;
        });

        $extendedType = $io->askQuestion($question);
        if (!isset($this->types[$extendedType])) {
            $this->types[$extendedType] = $extendedType;
            $extendedType = (new ReflectionClass($extendedType))->getShortName();
        }
        $input->setArgument('extended_type', $extendedType);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $classNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Form\\Extension\\',
            'TypeExtension'
        );

        $useStatements = new UseStatementGenerator([
            AbstractTypeExtension::class,
            $this->types[$input->getArgument('extended_type')],
        ]);

        $generator->generateClass(
            $classNameDetails->getFullName(),
            'form/TypeExtension.tpl.php',
            [
                'use_statements' => $useStatements,
                'extended_type' => $input->getArgument('extended_type'),
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $io->text([
            'Next: Open your new form extension class and start customizing it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/form/create_form_type_extension.html</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            AbstractTypeExtension::class,
            'form'
        );
    }
}
