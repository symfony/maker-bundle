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
use Doctrine\Common\Inflector\Inflector;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineEntityHelper;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\GeneratorHelper;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Validator\Validation;

/**
 * @author Sadicov Vladimir <sadikoff@gmail.com>
 */
final class MakeCrud extends AbstractMaker
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
        return 'make:crud';
    }

    /**
     * {@inheritdoc}
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates crud for Doctrine entity class')
            ->addArgument('entity-class', InputArgument::OPTIONAL, sprintf('The class name of the entity to create crud (e.g. <fg=yellow>%s</>)', Str::asClassName(Str::getRandomTerm())))
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeCrud.txt'))
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $entityClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('entity-class'),
            'Entity\\'
        );

        $controllerClassNameDetails = $generator->createClassNameDetails(
            $entityClassNameDetails->getRelativeNameWithoutSuffix(),
            'Controller\\',
            'Controller'
        );

        $formClassNameDetails = $generator->createClassNameDetails(
            $entityClassNameDetails->getRelativeNameWithoutSuffix(),
            'Form\\',
            'Type'
        );

        $metadata = $this->entityHelper->getEntityMetadata($entityClassNameDetails->getFullName());
        $entityVarPlural = lcfirst(Inflector::pluralize($entityClassNameDetails->getShortName()));
        $entityVarSingular = lcfirst(Inflector::singularize($entityClassNameDetails->getShortName()));
        $routeName = Str::asRouteName($controllerClassNameDetails->getRelativeNameWithoutSuffix());

        $generator->generateClass(
            $controllerClassNameDetails->getFullName(),
            'crud/controller/Controller.tpl.php',
            [
                'entity_full_class_name' => $entityClassNameDetails->getFullName(),
                'entity_class_name' => $entityClassNameDetails->getShortName(),
                'form_full_class_name' => $formClassNameDetails->getFullName(),
                'form_class_name' => $formClassNameDetails->getShortName(),
                'route_path' => Str::asRoutePath($controllerClassNameDetails->getRelativeNameWithoutSuffix()),
                'route_name' => $routeName,
                'entity_var_plural' => $entityVarPlural,
                'entity_var_singular' => $entityVarSingular,
                'entity_identifier' => $metadata->identifier[0],
            ]
        );

        $formFields = $this->entityHelper->getFormFieldsFromEntity($entityClassNameDetails->getFullName());

        $helper = new GeneratorHelper();

        $generator->generateClass(
            $formClassNameDetails->getFullName(),
            'form/Type.tpl.php',
            [
                'entity_class_exists' => true,
                'entity_full_class_name' => $entityClassNameDetails->getFullName(),
                'entity_class_name' => $entityClassNameDetails->getShortName(),
                'form_fields' => $formFields,
                'helper' => $helper,
            ]
        );

        $baseLayoutExists = $this->fileManager->fileExists('templates/base.html.twig');
        $templatesPath = Str::asFilePath($controllerClassNameDetails->getRelativeNameWithoutSuffix());

        $templates = [
            '_delete_form' => [
                'route_name' => $routeName,
                'entity_var_singular' => $entityVarSingular,
                'entity_identifier' => $metadata->identifier[0],
            ],
            '_form' => [],
            'edit' => [
                'helper' => $helper,
                'base_layout_exists' => $baseLayoutExists,
                'entity_class_name' => $entityClassNameDetails->getShortName(),
                'entity_var_singular' => $entityVarSingular,
                'entity_identifier' => $metadata->identifier[0],
                'route_name' => $routeName,
            ],
            'index' => [
                'helper' => $helper,
                'base_layout_exists' => $baseLayoutExists,
                'entity_class_name' => $entityClassNameDetails->getShortName(),
                'entity_var_plural' => $entityVarPlural,
                'entity_var_singular' => $entityVarSingular,
                'entity_identifier' => $metadata->identifier[0],
                'entity_fields' => $metadata->fieldMappings,
                'route_name' => $routeName,
            ],
            'new' => [
                'helper' => $helper,
                'base_layout_exists' => $baseLayoutExists,
                'entity_class_name' => $entityClassNameDetails->getShortName(),
                'route_name' => $routeName,
            ],
            'show' => [
                'helper' => $helper,
                'base_layout_exists' => $baseLayoutExists,
                'entity_class_name' => $entityClassNameDetails->getShortName(),
                'entity_var_singular' => $entityVarSingular,
                'entity_identifier' => $metadata->identifier[0],
                'entity_fields' => $metadata->fieldMappings,
                'route_name' => $routeName,
            ],
        ];

        foreach ($templates as $template => $variables) {
            $generator->generateFile(
                'templates/'.$templatesPath.'/'.$template.'.html.twig',
                'crud/templates/'.$template.'.tpl.php',
                $variables
            );
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text('Next: Check your new crud!');
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Route::class,
            'annotations'
        );

        $dependencies->addClassDependency(
            AbstractType::class,
            'form'
        );

        $dependencies->addClassDependency(
            Validation::class,
            'validator'
        );

        $dependencies->addClassDependency(
            TwigBundle::class,
            'twig-bundle'
        );

        $dependencies->addClassDependency(
            DoctrineBundle::class,
            'orm-pack'
        );

        $dependencies->addClassDependency(
            CsrfTokenManager::class,
            'security-csrf'
        );
    }
}
