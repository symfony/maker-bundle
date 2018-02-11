<?php

namespace Symfony\Bundle\MakerBundle\Maker;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Psr\Container\ContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\GeneratorHelper;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validation;

/**
 * @author Sadicov Vladimir <sadikoff@gmail.com>
 */
final class MakeCrud extends AbstractMaker
{
    private $router;
    private $fileManager;
    private $locator;

    public function __construct(RouterInterface $router, FileManager $fileManager, ContainerInterface $serviceLocator)
    {
        $this->router = $router;
        $this->fileManager = $fileManager;
        $this->locator = $serviceLocator;
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

    /**
     * {@inheritdoc}
     */
    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(InputInterface $input): array
    {
        $entityClassName = Str::asClassName($input->getArgument('entity-class'));
        Validator::validateClassName($entityClassName);
        $controllerClassName = Str::asClassName($entityClassName, 'Controller');
        Validator::validateClassName($controllerClassName);
        $formClassName = Str::asClassName($entityClassName, 'Type');
        Validator::validateClassName($formClassName);

        $metadata = $this->getEntityMetadata($entityClassName);

        $baseLayoutExists = $this->fileManager->fileExists('templates/base.html.twig');

        $helper = new GeneratorHelper();

        return array(
            'helper' => $helper,
            'controller_class_name' => $controllerClassName,
            'entity_var_plural' => lcfirst(Inflector::pluralize($entityClassName)),
            'entity_var_singular' => lcfirst(Inflector::singularize($entityClassName)),
            'entity_class_name' => $entityClassName,
            'entity_identifier' => $metadata->identifier[0],
            'entity_fields' => $metadata->fieldMappings,
            'form_class_name' => $formClassName,
            'route_path' => Str::asRoutePath(str_replace('Controller', '', $controllerClassName)),
            'route_name' => Str::asRouteName(str_replace('Controller', '', $controllerClassName)),
            'base_layout_exists' => $baseLayoutExists,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFiles(array $params): array
    {
        return array(
            __DIR__.'/../Resources/skeleton/crud/controller/Controller.tpl.php' => 'src/Controller/'.$params['controller_class_name'].'.php',
//            __DIR__.'/../Resources/skeleton/crud/form/Type.tpl.php' => 'src/Form/'.$params['form_class_name'].'.php',
            __DIR__.'/../Resources/skeleton/crud/templates/_delete_form.tpl.php' => 'templates/'.$params['route_name'].'/_delete_form.html.twig',
            __DIR__.'/../Resources/skeleton/crud/templates/_form.tpl.php' => 'templates/'.$params['route_name'].'/_form.html.twig',
            __DIR__.'/../Resources/skeleton/crud/templates/index.tpl.php' => 'templates/'.$params['route_name'].'/index.html.twig',
            __DIR__.'/../Resources/skeleton/crud/templates/show.tpl.php' => 'templates/'.$params['route_name'].'/show.html.twig',
            __DIR__.'/../Resources/skeleton/crud/templates/new.tpl.php' => 'templates/'.$params['route_name'].'/new.html.twig',
            __DIR__.'/../Resources/skeleton/crud/templates/edit.tpl.php' => 'templates/'.$params['route_name'].'/edit.html.twig',
        );
    }

    public function writeSuccessMessage(array $params, ConsoleStyle $io)
    {
        parent::writeSuccessMessage($params, $io);

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

//        $dependencies->addClassDependency(
//            AbstractType::class,
//            // technically only form is needed, but the user will *probably* also want validation
//            'form'
//        );
//
//        $dependencies->addClassDependency(
//            Validation::class,
//            'validator',
//            // add as an optional dependency: the user *probably* wants validation
//            false
//        );

        $dependencies->addClassDependency(
            TwigBundle::class,
            'twig-bundle'
        );

        $dependencies->addClassDependency(
            EntityManager::class,
            'orm-pack'
        );
    }

    private function getEntityMetadata($entityClassName)
    {
        try {
            return $this->locator->get('doctrine')->getManager()->getClassMetadata('App\\Entity\\'.$entityClassName);
        } catch (\Doctrine\Common\Persistence\Mapping\MappingException $exception) {
            throw new RuntimeCommandException(sprintf('Entity "%s" doesn\'t exists in your project. May be you would like to create it with "make:entity" command?', $entityClassName));
        }
    }
}
