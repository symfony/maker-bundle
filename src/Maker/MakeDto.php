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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassDetails;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Util\DTOClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @author Clemens Krack <info@clemenskrack.com>
 */
final class MakeDto extends AbstractMaker
{
    private $doctrineHelper;
    private $fileManager;
    private $validator;
    private $validatorClassMetadata;

    private const DTO_STYLES = [
        1 => 'Mutable, with getters & setters (default)',
        2 => 'Mutable, with public properties',
        3 => 'Immutable, with getters only',
    ];

    private const TEMPLATE_NAMES = [
        1 => 'MutableGettersSetters',
        2 => 'MutablePublic',
        3 => 'ImmutableGetters',
    ];

    private const MUTATOR_NAME_PREFIX = 'updateFrom';
    private const ARGUMENT_NAME = 'name';
    private const ARGUMENT_ENTITY = 'entity';
    private const OPTION_STYLE = 'style';
    private const OPTION_MUTATOR = 'mutator';

    // Did we import assert annotations?
    private $assertionsImported = false;

    // Are there differences in the validation constraints between metadata (includes annotations, xml, yaml) and annotations?
    private $suspectYamlXmlValidations = false;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        FileManager $fileManager,
        ValidatorInterface $validator = null
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->fileManager = $fileManager;
        $this->validator = $validator;
    }

    public static function getCommandName(): string
    {
        return 'make:dto';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new "data transfer object" (DTO) class from a Doctrine entity')
            ->addArgument(self::ARGUMENT_NAME, InputArgument::OPTIONAL, sprintf('The name of the DTO class (e.g. <fg=yellow>%sData</>)', Str::asClassName(Str::getRandomTerm())))
            ->addArgument(self::ARGUMENT_ENTITY, InputArgument::OPTIONAL, 'The name of Entity that the DTO will be bound to')
            ->addOption(self::OPTION_STYLE, null, InputOption::VALUE_REQUIRED, 'The style of the DTO')
            ->addOption(self::OPTION_MUTATOR, null, InputOption::VALUE_REQUIRED, 'Whether a mutator should be added to the entity', true)
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeDto.txt'))
        ;

        $inputConf->setArgumentAsNonInteractive(self::ARGUMENT_NAME);
        $inputConf->setArgumentAsNonInteractive(self::ARGUMENT_ENTITY);
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (null === $input->getArgument(self::ARGUMENT_NAME)) {
            $argument = $command->getDefinition()->getArgument(self::ARGUMENT_NAME);
            $question = $this->createDataClassQuestion($argument->getDescription());
            $value = $io->askQuestion($question);
            $input->setArgument(self::ARGUMENT_NAME, $value);
        }

        if (null === $input->getArgument(self::ARGUMENT_ENTITY)) {
            $argument = $command->getDefinition()->getArgument(self::ARGUMENT_ENTITY);
            $question = $this->createEntityClassQuestion($argument->getDescription());
            $value = $io->askQuestion($question);
            $input->setArgument(self::ARGUMENT_ENTITY, $value);
        }

        $input->setOption(self::OPTION_STYLE, $io->choice(
            'Specify the type of DTO you want:',
            self::DTO_STYLES
        ));

        $input->setOption(self::OPTION_MUTATOR, $io->confirm(
            'Add mutator method to Entity (to set data from the DTO)?'
        ));
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $dataClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument(self::ARGUMENT_NAME),
            'Dto\\',
            'Data'
        );

        $entity = $input->getArgument(self::ARGUMENT_ENTITY);

        $entityDetails = $generator->createClassNameDetails(
            $entity,
            'Entity\\'
        );

        // Verify that class is an entity
        if (!$this->doctrineHelper->isClassAMappedEntity($entityDetails->getFullName())) {
            throw new RuntimeCommandException('The bound class is not a valid doctrine entity.');
        }

        $fields = $this->getFilteredFieldMappings($entityDetails->getFullName());

        $missingGettersSetters = $this->checkMissingGettersSetters($fields, $entityDetails->getFullName());

        $entityVars = [
            'entity_full_class_name' => $entityDetails->getFullName(),
            'entity_class_name' => $entityDetails->getShortName(),
        ];

        $DTOClassPath = $generator->generateClass(
            $dataClassNameDetails->getFullName(),
            $this->getDtoTemplateName($input),
            array_merge(
                [
                    'fields' => $fields,
                ],
                $entityVars
            )
        );

        $generator->writeChanges();

        $dtoManipulator = $this->createDTOClassManipulator($DTOClassPath, $input);
        $this->createProperties($dtoManipulator, $entityDetails, $fields);

        if ($this->shouldCreateConstructor($input)) {
            $dtoManipulator->createNamedConstructor($entityDetails, $dataClassNameDetails->getShortName());
            $dtoManipulator->createConstructor();
        }

        $this->fileManager->dumpFile(
            $DTOClassPath,
            $dtoManipulator->getSourceCode()
        );

        if ($input->getOption(self::OPTION_MUTATOR)) {
            $entityClassDetails = new ClassDetails($entityDetails->getFullName());
            $entityPath = $entityClassDetails->getPath();
            $entityManipulator = $this->createEntityClassManipulator($entityPath, $io, false);

            $this->createEntityMutator(
                $entityManipulator,
                $dataClassNameDetails,
                $fields,
                $this->shouldGenerateGetters($input)
            );

            $this->fileManager->dumpFile($entityPath, $entityManipulator->getSourceCode());
        }

        $this->writeSuccessMessage($io);

        if (true === $this->assertionsImported) {
            $io->note([
                'The maker imported assertion annotations.',
                'Consider removing them from the entity or make sure to keep them updated in both places.',
            ]);
        }

        if (true === $this->suspectYamlXmlValidations) {
            $io->note([
                'The entity possibly uses Yaml/Xml validators.',
                'Make sure to update the validations to include the new DTO class.',
            ]);
        }

        if ($missingGettersSetters) {
            $io->note([
                'The maker found missing getters/setters for properties in the entity.',
                'Please review the generated DTO for @todo comments.',
            ]);
        }

        $nextSteps = [
            'Next:',
            sprintf('- Review the new DTO <info>%s</info>', $DTOClassPath),
            $input->getOption(self::OPTION_MUTATOR) ? sprintf(
                '- Review the generated mutator method <info>%s()</info> in <info>%s</info>',
                $entityDetails->getShortName().'::'.self::MUTATOR_NAME_PREFIX.$dataClassNameDetails->getShortName(),
                $this->fileManager->relativizePath($entityClassDetails->getPath())
            ) : null,
            'Then: Create a form for this DTO by running:',
            sprintf('<info>$ php bin/console make:form %s</info>', $entityDetails->getShortName()),
            sprintf('and enter <info>\\%s</>', $dataClassNameDetails->getFullName()),
            '',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/forms/data_transfer_objects.html</>',
        ];

        $io->text($nextSteps);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Validation::class,
            'validator',
            // add as an optional dependency: the user *probably* wants validation
            false
        );
    }

    /**
     * Get field mappings from class metadata (used to copy annotations and generate properties).
     */
    private function getFilteredFieldMappings(string $entityClassName): array
    {
        /**
         * @var ClassMetaData
         */
        $metaData = $this->doctrineHelper->getMetadata($entityClassName);

        return $this->filterIdentifiersFromFields($metaData->fieldMappings, $metaData);
    }

    private function filterIdentifiersFromFields($fields, $metaData): array
    {
        return array_filter($fields, function ($field) use ($metaData) {
            return !$metaData->isIdentifier($field['fieldName']);
        });
    }

    private function checkMissingGettersSetters(array &$fields, string $entityClassName): bool
    {
        $missingGettersSetters = false;
        foreach (array_keys($fields) as $fieldName) {
            $fields[$fieldName]['hasSetter'] = $this->entityHasSetter($entityClassName, $fieldName);
            $fields[$fieldName]['hasGetter'] = $this->entityHasGetter($entityClassName, $fieldName);

            if (!$fields[$fieldName]['hasGetter'] || !$fields[$fieldName]['hasSetter']) {
                $missingGettersSetters = true;
            }
        }

        return $missingGettersSetters;
    }

    private function createEntityClassQuestion(string $questionText): Question
    {
        $question = new Question($questionText);
        $entities = $this->doctrineHelper->getEntitiesForAutocomplete();
        $question->setAutocompleterValues($entities);
        $question->setMaxAttempts(10);
        $question->setValidator(function ($answer) use ($entities) {return Validator::existsOrNull($answer, $entities); });

        return $question;
    }

    private function createDataClassQuestion(string $questionText): Question
    {
        $question = new Question($questionText);
        $question->setValidator([Validator::class, 'notBlank']);
        $question->setMaxAttempts(10);

        return $question;
    }

    private function createProperties($dtoManipulator, $entityDetails, $fields)
    {
        foreach ($fields as $fieldName => $mapping) {
            $annotationReader = new AnnotationReader();

            $fullClassName = $mapping['declared'] ?? $entityDetails->getFullName();

            // Property Annotations
            $reflectionProperty = new \ReflectionProperty($fullClassName, $fieldName);
            $propertyAnnotations = $annotationReader->getPropertyAnnotations($reflectionProperty);

            // Passed to the ClassManipulator
            $annotationComments = [];

            // Count the Constraints for comparison with the Validator
            $constraintCount = 0;

            foreach ($propertyAnnotations as $annotation) {
                // We want to copy the asserts, so look for their interface
                if ($annotation instanceof Constraint) {
                    // Set flag for use in result message
                    $this->assertionsImported = true;
                    ++$constraintCount;
                    $annotationComments[] = $dtoManipulator->buildAnnotationLine('@Assert\\'.(new \ReflectionClass($annotation))->getShortName(), $this->getAnnotationAsString($annotation));
                }
            }

            // Compare the amount of constraints in annotations with those in the complete validator-metadata for the entity
            if (false === $this->hasAsManyValidations($entityDetails->getFullName(), $fieldName, $constraintCount)) {
                $this->suspectYamlXmlValidations = true;
            }

            $dtoManipulator->addEntityField($fieldName, $mapping, $annotationComments);
        }

        // Add use statement for validation annotations if necessary
        if (true == $this->assertionsImported) {
            // The use of an alias is not supposed, but it works fine and we don't use the returned value.
            $dtoManipulator->addUseStatementIfNecessary('Symfony\Component\Validator\Constraints as Assert');
        }
    }

    private function createEntityMutator(ClassSourceManipulator $entityManipulator, ClassNameDetails $dataClassNameDetails, array $fields, bool $dtoHasGetters)
    {
        $dataClassUseName = $entityManipulator->addUseStatementIfNecessary($dataClassNameDetails->getFullName());

        $updateFromMethodBuilder = $entityManipulator->createMethodBuilder(self::MUTATOR_NAME_PREFIX.$dataClassNameDetails->getShortName(), null, true);
        $updateFromMethodBuilder->addParam(
            (new \PhpParser\Builder\Param(lcfirst($dataClassNameDetails->getShortName())))->setTypeHint($dataClassUseName)
        );

        $methodBody = '<?php'.PHP_EOL;
        foreach ($fields as $propertyName => $mapping) {
            $assignedValue = ($dtoHasGetters) ? '$'.lcfirst($dataClassNameDetails->getShortName()).'->get'.Str::asCamelCase($propertyName).'()' : '$'.lcfirst($dataClassNameDetails->getShortName()).'->'.$propertyName;
            if (false === $mapping['hasSetter']) {
                $methodBody .= '$this->'.$propertyName.' = '.$assignedValue.';'.PHP_EOL;
            } else {
                $methodBody .= '$this->set'.Str::asCamelCase($propertyName).'('.$assignedValue.');'.PHP_EOL;
            }
        }

        $entityManipulator->addMethodBody($updateFromMethodBuilder, $methodBody);
        $entityManipulator->addMethodBuilder($updateFromMethodBuilder);
    }

    private function createDTOClassManipulator(string $classPath, InputInterface $input): DTOClassSourceManipulator
    {
        return new DTOClassSourceManipulator(
            $this->fileManager->getFileContents($classPath),
            // overwrite existing methods
            true,
            // use annotations
            true,
            // use fluent mutators
            true,
            // generate getters?
            $this->shouldGenerateGetters($input),
            // generate setters?
            $this->shouldGenerateSetters($input),
            // Public properties?
            $this->shouldGeneratePublicProperties($input),
            // Constructor?
            $this->shouldCreateConstructor($input)
        );
    }

    private function createEntityClassManipulator(string $path, ConsoleStyle $io, bool $overwrite): ClassSourceManipulator
    {
        $manipulator = new ClassSourceManipulator($this->fileManager->getFileContents($path), $overwrite);
        $manipulator->setIo($io);

        return $manipulator;
    }

    private function getAnnotationAsString(Constraint $annotation)
    {
        // We typecast, because array_diff expects arrays and both functions can return null.
        return array_diff((array) get_object_vars($annotation), (array) get_class_vars(\get_class($annotation)));
    }

    private function hasAsManyValidations($entityClassname, $fieldName, $constraintCount)
    {
        if (null === $this->validator) {
            return 0 == $constraintCount;
        }

        // lazily build validatorClassMetadata
        if (null === $this->validatorClassMetadata) {
            $this->validatorClassMetadata = $this->validator->getMetadataFor($entityClassname);
        }

        $propertyMetadata = $this->validatorClassMetadata->getPropertyMetadata($fieldName);

        $metadataConstraintCount = 0;
        foreach ($propertyMetadata as $metadata) {
            if (isset($metadata->constraints)) {
                $metadataConstraintCount += is_countable($metadata->constraints) ? \count($metadata->constraints) : 0;
            }
        }

        return $metadataConstraintCount == $constraintCount;
    }

    private function entityHasGetter($entityClassName, $propertyName): bool
    {
        return method_exists($entityClassName, sprintf('get%s', Str::asCamelCase($propertyName)));
    }

    private function entityHasSetter($entityClassName, $propertyName): bool
    {
        return method_exists($entityClassName, sprintf('set%s', Str::asCamelCase($propertyName)));
    }

    private function getDtoTemplateName(InputInterface $input): string
    {
        return __DIR__
            .'/../Resources/skeleton/dto/'
            .self::TEMPLATE_NAMES[array_search($input->getOption(self::OPTION_STYLE), self::DTO_STYLES)]
            .'Dto.tpl.php';
    }

    private function shouldGeneratePublicProperties(InputInterface $input): bool
    {
        return 2 === array_search($input->getOption(self::OPTION_STYLE), self::DTO_STYLES);
    }

    private function shouldGenerateSetters(InputInterface $input): bool
    {
        return 1 === array_search($input->getOption(self::OPTION_STYLE), self::DTO_STYLES);
    }

    private function shouldGenerateGetters(InputInterface $input): bool
    {
        return \in_array(array_search($input->getOption(self::OPTION_STYLE), self::DTO_STYLES), [1, 3]);
    }

    private function shouldCreateConstructor(InputInterface $input): bool
    {
        return 3 === array_search($input->getOption(self::OPTION_STYLE), self::DTO_STYLES);
    }
}
