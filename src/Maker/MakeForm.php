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

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineEntityHelper;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\GeneratorHelper;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Validation;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Sadicov Vladimir <sadikoff@gmail.com>
 */
final class MakeForm extends AbstractMaker
{
    private $fileManager;
    private $entityHelper;

    public function __construct(FileManager $fileManager, DoctrineEntityHelper $entityHelper)
    {
        $this->fileManager = $fileManager;
        $this->entityHelper = $entityHelper;
    }

    public static function getCommandName(): string
    {
        return 'make:form';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new form class')
            ->addArgument('name', InputArgument::OPTIONAL, sprintf('The name of the form class (e.g. <fg=yellow>%sType</>)', Str::asClassName(Str::getRandomTerm())))
            ->addOption('entity', null, InputOption::VALUE_NONE, 'Generate form class from existing entity')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeForm.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        $entityFormGeneration = $input->getOption('entity');

        if ($this->entityHelper->isDoctrineConnected()) {
            $entityClassName = Str::removeSuffix(Str::asClassName($input->getArgument('name')), 'Type');

            $entityExists = $this->fileManager->fileExists('src/Entity/'.$entityClassName.'.php');

            if ($entityFormGeneration && !$entityExists) {
                throw new RuntimeCommandException(sprintf('Entity "%s" doesn\'t exists in your project. May be you would like to create it with "make:entity" command?', $entityClassName));
            }

            if ((!$entityFormGeneration && !$entityExists) || $entityFormGeneration) {
                return;
            }

            $io->block(
                sprintf('Entity "%s" found in your project', $entityClassName),
                null,
                'fg=yellow'
            );
            $entityFormGeneration = $io->confirm('Do you want to generate a form for an entity?', true);

            $input->setOption('entity', $entityFormGeneration);
        } elseif ($entityFormGeneration) {
            $io->block([
                'Doctrine not found, falling back to simple form generation',
            ], null, 'fg=yellow');
            $input->setOption('entity', false);
        }
    }

    public function getParameters(InputInterface $input): array
    {
        $formClassName = Str::asClassName($input->getArgument('name'), 'Type');
        Validator::validateClassName($formClassName);

        $entityFormGeneration = $input->getOption('entity');

        $entityClassName = $entityFormGeneration ? Str::removeSuffix($formClassName, 'Type') : false;

        $formFields = $entityFormGeneration ? $this->entityHelper->getFormFieldsFromEntity($entityClassName) : ['field_name'];

        $helper = new GeneratorHelper();

        return [
            'helper' => $helper,
            'form_class_name' => $formClassName,
            'entity_class_name' => $entityClassName,
            'form_fields' => $formFields,
        ];
    }

    public function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/form/Type.tpl.php' => 'src/Form/'.$params['form_class_name'].'.php',
        ];
    }

    public function writeSuccessMessage(array $params, ConsoleStyle $io)
    {
        parent::writeSuccessMessage($params, $io);

        if (!$params['entity_class_name']) {
            $io->text([
                'Next: Add fields to your form and start using it.',
                'Find the documentation at <fg=yellow>https://symfony.com/doc/current/forms.html</>',
            ]);
        }
    }

    public function configureDependencies(DependencyBuilder $dependencies)
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

        $dependencies->addClassDependency(
            DoctrineBundle::class,
            'orm',
            false
        );
    }
}
