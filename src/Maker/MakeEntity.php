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

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Doctrine\EntityClassGenerator;
use Symfony\Bundle\MakerBundle\Doctrine\ORMDependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputAwareMakerInterface;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\MakeEntityHelper;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Antoine Michelet <jean.marcel.michelet@gmail.com>
 */
final class MakeEntity extends AbstractMaker implements InputAwareMakerInterface
{
    private $fileManager;
    private $doctrineHelper;
    private $generator;
    private $entityClassGenerator;
    private $entityHelper;

    public function __construct(FileManager $fileManager, DoctrineHelper $doctrineHelper, string $projectDirectory, Generator $generator = null, EntityClassGenerator $entityClassGenerator = null, MakeEntityHelper $entityHelper)
    {
        $this->fileManager = $fileManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->entityHelper = $entityHelper;
        // $projectDirectory is unused, argument kept for BC

        if (null === $generator) {
            @trigger_error(sprintf('Passing a "%s" instance as 4th argument is mandatory since version 1.5.', Generator::class), E_USER_DEPRECATED);
            $this->generator = new Generator($fileManager, 'App\\');
        } else {
            $this->generator = $generator;
        }

        if (null === $entityClassGenerator) {
            @trigger_error(sprintf('Passing a "%s" instance as 5th argument is mandatory since version 1.15.1', EntityClassGenerator::class), E_USER_DEPRECATED);
            $this->entityClassGenerator = new EntityClassGenerator($generator, $this->doctrineHelper);
        } else {
            $this->entityClassGenerator = $entityClassGenerator;
        }
    }

    public static function getCommandName(): string
    {
        return 'make:entity';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates or updates a Doctrine entity class, and optionally an API Platform resource')
            ->addArgument('name', InputArgument::OPTIONAL, sprintf('Class name of the entity to create or update (e.g. <fg=yellow>%s</>)', Str::asClassName(Str::getRandomTerm())))
            ->addOption('api-resource', 'a', InputOption::VALUE_NONE, 'Mark this class as an API Platform resource (expose a CRUD API for it)')
            ->addOption('regenerate', null, InputOption::VALUE_NONE, 'Instead of adding new fields, simply generate the methods (e.g. getter/setter) for existing fields')
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'Overwrite any existing getter/setter methods')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeEntity.txt'))
        ;

        $inputConf->setArgumentAsNonInteractive('name');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if ($input->getArgument('name')) {
            return;
        }

        if ($input->getOption('regenerate')) {
            $io->block([
                'This command will generate any missing methods (e.g. getters & setters) for a class or all classes in a namespace.',
                'To overwrite any existing methods, re-run this command with the --overwrite flag',
            ], null, 'fg=yellow');
            $classOrNamespace = $io->ask('Enter a class or namespace to regenerate', $this->entityHelper->getEntityNamespace(), [Validator::class, 'notBlank']);

            $input->setArgument('name', $classOrNamespace);

            return;
        }

        $argument = $command->getDefinition()->getArgument('name');
        $question = $this->entityHelper->createEntityClassQuestion($argument->getDescription());
        $value = $io->askQuestion($question);

        $input->setArgument('name', $value);

        if ($input->getOption('api-resource')) {
            @trigger_error(sprintf('The "%s" comand option is deprecated since Symfony 5.2 use the command "%s" instead.', 'api-resource', 'make:api:resource'), E_USER_DEPRECATED);
        }

        if (
            !$input->getOption('api-resource') &&
            class_exists(ApiResource::class) &&
            !class_exists($this->generator->createClassNameDetails($value, 'Entity\\')->getFullName())
        ) {
            $description = $command->getDefinition()->getOption('api-resource')->getDescription();
            $question = new ConfirmationQuestion($description, false);
            $value = $io->askQuestion($question);

            $input->setOption('api-resource', $value);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $overwrite = $input->getOption('overwrite');

        // the regenerate option has entirely custom behavior
        if ($input->getOption('regenerate')) {
            $this->entityHelper->regenerateEntities($input->getArgument('name'), $overwrite, $generator);
            $this->writeSuccessMessage($io);

            return;
        }

        $entityClassDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Entity\\'
        );

        $classExists = class_exists($entityClassDetails->getFullName());
        if (!$classExists) {
            $entityPath = $this->entityClassGenerator->generateEntityClass(
                $entityClassDetails,
                $input->getOption('api-resource')
            );

            $generator->writeChanges();
        }

        if (!$this->entityHelper->doesEntityUseAnnotationMapping($entityClassDetails->getFullName())) {
            throw new RuntimeCommandException(sprintf('Only annotation mapping is supported by make:entity, but the <info>%s</info> class uses a different format. If you would like this command to generate the properties & getter/setter methods, add your mapping configuration, and then re-run this command with the <info>--regenerate</info> flag.', $entityClassDetails->getFullName()));
        }

        if ($classExists) {
            $entityPath = $this->entityHelper->getPathOfClass($entityClassDetails->getFullName());

            $io->text([
                'Your entity already exists! So let\'s add some new fields!',
            ]);
        } else {
            $io->text([
                '',
                'Entity generated! Now let\'s add some fields!',
                'You can always add more fields later manually or by re-running this command.',
            ]);
        }

        $this->entityHelper->generateEntityFields($io, $entityClassDetails, $entityPath, $overwrite);
    }

    public function configureDependencies(DependencyBuilder $dependencies, InputInterface $input = null)
    {
        if (null !== $input && $input->getOption('api-resource')) {
            $dependencies->addClassDependency(
                ApiResource::class,
                'api'
            );
        }

        ORMDependencyBuilder::buildDependencies($dependencies);
    }
}
