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
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Validation;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeFormCommand extends AbstractCommand
{
    protected static $defaultName = 'make:form';

    public function configure()
    {
        $this
            ->setDescription('Creates a new form class')
            ->addArgument('name', InputArgument::OPTIONAL, sprintf('The name of the form class (e.g. <fg=yellow>%sType</>)', Str::asClassName(Str::getRandomTerm())))
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeForm.txt'))
        ;
    }

    protected function getParameters(): array
    {
        $formName = $this->input->getArgument('name');
        if(empty($formName)) {
            throw new RuntimeCommandException("You must provide the name of the form you want to create");
        }

        $formClassName = Str::asClassName($formName, 'Type');
        Validator::validateClassName($formClassName);
        $entityClassName = Str::removeSuffix($formClassName, 'Type');

        return [
            'form_class_name' => $formClassName,
            'entity_class_name' => $entityClassName,
        ];
    }

    protected function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/form/Type.php.txt' => 'src/Form/'.$params['form_class_name'].'.php',
        ];
    }

    protected function getResultMessage(array $params): string
    {
        return sprintf('<fg=blue>%s</> created successfully.', $params['form_class_name']);
    }

    protected function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        $io->text([
            'Next: Add fields to your form and start using it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/forms.html</>'
        ]);
    }

    protected function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            AbstractType::class,
            // technically only form is needed, but the user will *probably* also want validation
            'form'
        );

        $dependencies->addClassDependency(
            Validation::class,
            'validator',
            // add as an optional dependency: the user *probably* wants validation
            false
        );
    }
}
