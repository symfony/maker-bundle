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
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Renderer\FormTypeRenderer;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Validator\Validation;

/**
 * @author Sadicov Vladimir <sadikoff@gmail.com>
 */
final class MakeCrud extends AbstractMaker
{
    private Inflector $inflector;
    private string $controllerClassName;
    private bool $generateTests = false;

    public function __construct(private DoctrineHelper $doctrineHelper, private FormTypeRenderer $formTypeRenderer)
    {
        $this->inflector = InflectorFactory::create()->build();
    }

    public static function getCommandName(): string
    {
        return 'make:crud';
    }

    public static function getCommandDescription(): string
    {
        return 'Create CRUD for Doctrine entity class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('entity-class', InputArgument::OPTIONAL, sprintf('The class name of the entity to create CRUD (e.g. <fg=yellow>%s</>)', Str::asClassName(Str::getRandomTerm())))
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeCrud.txt'))
        ;

        $inputConfig->setArgumentAsNonInteractive('entity-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (null === $input->getArgument('entity-class')) {
            $argument = $command->getDefinition()->getArgument('entity-class');

            $entities = $this->doctrineHelper->getEntitiesForAutocomplete();

            $question = new Question($argument->getDescription());
            $question->setAutocompleterValues($entities);

            $value = $io->askQuestion($question);

            $input->setArgument('entity-class', $value);
        }

        $defaultControllerClass = Str::asClassName(sprintf('%s Controller', $input->getArgument('entity-class')));

        $this->controllerClassName = $io->ask(
            sprintf('Choose a name for your controller class (e.g. <fg=yellow>%s</>)', $defaultControllerClass),
            $defaultControllerClass
        );

        $this->generateTests = $io->confirm('Do you want to generate tests for the controller? [Experimental]', false);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $entityClassDetails = $generator->createClassNameDetails(
            Validator::entityExists($input->getArgument('entity-class'), $this->doctrineHelper->getEntitiesForAutocomplete()),
            'Entity\\'
        );

        $entityDoctrineDetails = $this->doctrineHelper->createDoctrineDetails($entityClassDetails->getFullName());

        $repositoryVars = [];
        $repositoryClassName = EntityManagerInterface::class;

        if (null !== $entityDoctrineDetails->getRepositoryClass()) {
            $repositoryClassDetails = $generator->createClassNameDetails(
                '\\'.$entityDoctrineDetails->getRepositoryClass(),
                'Repository\\',
                'Repository'
            );

            $repositoryClassName = $repositoryClassDetails->getFullName();

            $repositoryVars = [
                'repository_full_class_name' => $repositoryClassName,
                'repository_class_name' => $repositoryClassDetails->getShortName(),
                'repository_var' => lcfirst($this->inflector->singularize($repositoryClassDetails->getShortName())),
            ];
        }

        $controllerClassDetails = $generator->createClassNameDetails(
            $this->controllerClassName,
            'Controller\\',
            'Controller'
        );

        $iter = 0;
        do {
            $formClassDetails = $generator->createClassNameDetails(
                $entityClassDetails->getRelativeNameWithoutSuffix().($iter ?: '').'Type',
                'Form\\',
                'Type'
            );
            ++$iter;
        } while (class_exists($formClassDetails->getFullName()));

        $entityVarPlural = lcfirst($this->inflector->pluralize($entityClassDetails->getShortName()));
        $entityVarSingular = lcfirst($this->inflector->singularize($entityClassDetails->getShortName()));

        $entityTwigVarPlural = Str::asTwigVariable($entityVarPlural);
        $entityTwigVarSingular = Str::asTwigVariable($entityVarSingular);

        $routeName = Str::asRouteName($controllerClassDetails->getRelativeNameWithoutSuffix());
        $templatesPath = Str::asFilePath($controllerClassDetails->getRelativeNameWithoutSuffix());

        $useStatements = new UseStatementGenerator([
            $entityClassDetails->getFullName(),
            $formClassDetails->getFullName(),
            $repositoryClassName,
            AbstractController::class,
            Request::class,
            Response::class,
            Route::class,
        ]);

        if (EntityManagerInterface::class !== $repositoryClassName) {
            $useStatements->addUseStatement(EntityManagerInterface::class);
        }

        $generator->generateController(
            $controllerClassDetails->getFullName(),
            'crud/controller/Controller.tpl.php',
            array_merge([
                'use_statements' => $useStatements,
                'entity_class_name' => $entityClassDetails->getShortName(),
                'form_class_name' => $formClassDetails->getShortName(),
                'route_path' => Str::asRoutePath($controllerClassDetails->getRelativeNameWithoutSuffix()),
                'route_name' => $routeName,
                'templates_path' => $templatesPath,
                'entity_var_plural' => $entityVarPlural,
                'entity_twig_var_plural' => $entityTwigVarPlural,
                'entity_var_singular' => $entityVarSingular,
                'entity_twig_var_singular' => $entityTwigVarSingular,
                'entity_identifier' => $entityDoctrineDetails->getIdentifier(),
            ],
                $repositoryVars
            )
        );

        $this->formTypeRenderer->render(
            $formClassDetails,
            $entityDoctrineDetails->getFormFields(),
            $entityClassDetails
        );

        $templates = [
            '_delete_form' => [
                'route_name' => $routeName,
                'entity_twig_var_singular' => $entityTwigVarSingular,
                'entity_identifier' => $entityDoctrineDetails->getIdentifier(),
            ],
            '_form' => [],
            'edit' => [
                'entity_class_name' => $entityClassDetails->getShortName(),
                'entity_twig_var_singular' => $entityTwigVarSingular,
                'entity_identifier' => $entityDoctrineDetails->getIdentifier(),
                'route_name' => $routeName,
                'templates_path' => $templatesPath,
            ],
            'index' => [
                'entity_class_name' => $entityClassDetails->getShortName(),
                'entity_twig_var_plural' => $entityTwigVarPlural,
                'entity_twig_var_singular' => $entityTwigVarSingular,
                'entity_identifier' => $entityDoctrineDetails->getIdentifier(),
                'entity_fields' => $entityDoctrineDetails->getDisplayFields(),
                'route_name' => $routeName,
            ],
            'new' => [
                'entity_class_name' => $entityClassDetails->getShortName(),
                'route_name' => $routeName,
                'templates_path' => $templatesPath,
            ],
            'show' => [
                'entity_class_name' => $entityClassDetails->getShortName(),
                'entity_twig_var_singular' => $entityTwigVarSingular,
                'entity_identifier' => $entityDoctrineDetails->getIdentifier(),
                'entity_fields' => $entityDoctrineDetails->getDisplayFields(),
                'route_name' => $routeName,
                'templates_path' => $templatesPath,
            ],
        ];

        foreach ($templates as $template => $variables) {
            $generator->generateTemplate(
                $templatesPath.'/'.$template.'.html.twig',
                'crud/templates/'.$template.'.tpl.php',
                $variables
            );
        }

        if ($this->generateTests) {
            $testClassDetails = $generator->createClassNameDetails(
                $entityClassDetails->getRelativeNameWithoutSuffix(),
                'Test\\Controller\\',
                'ControllerTest'
            );

            $useStatements = new UseStatementGenerator([
                $entityClassDetails->getFullName(),
                WebTestCase::class,
                KernelBrowser::class,
                $repositoryClassName,
            ]);

            $useStatements->addUseStatement(EntityRepository::class);

            if (EntityManagerInterface::class !== $repositoryClassName) {
                $useStatements->addUseStatement(EntityManagerInterface::class);
            }

            $generator->generateFile(
                'tests/Controller/'.$testClassDetails->getShortName().'.php',
                'crud/test/Test.EntityManager.tpl.php',
                [
                    'use_statements' => $useStatements,
                    'entity_full_class_name' => $entityClassDetails->getFullName(),
                    'entity_class_name' => $entityClassDetails->getShortName(),
                    'entity_var_singular' => $entityVarSingular,
                    'route_path' => Str::asRoutePath($controllerClassDetails->getRelativeNameWithoutSuffix()),
                    'route_name' => $routeName,
                    'class_name' => Str::getShortClassName($testClassDetails->getFullName()),
                    'namespace' => Str::getNamespace($testClassDetails->getFullName()),
                    'form_fields' => $entityDoctrineDetails->getFormFields(),
                    'repository_class_name' => EntityManagerInterface::class,
                    'form_field_prefix' => strtolower(Str::asSnakeCase($entityTwigVarSingular)),
                ]
            );

            if (!class_exists(WebTestCase::class)) {
                $io->caution('You\'ll need to install the `symfony/test-pack` to execute the tests for your new controller.');
            }
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text(sprintf('Next: Check your new CRUD by going to <fg=yellow>%s/</>', Str::asRoutePath($controllerClassDetails->getRelativeNameWithoutSuffix())));
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            Route::class,
            'router'
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
            'orm'
        );

        $dependencies->addClassDependency(
            CsrfTokenManager::class,
            'security-csrf'
        );
    }
}
