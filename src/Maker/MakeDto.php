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
use Symfony\Bundle\MakerBundle\Util\DTOClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Clemens Krack <info@clemenskrack.com>
 */
final class MakeDto extends AbstractMaker
{
    private $doctrineHelper;
    private $fileManager;
    private $validator;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        FileManager $fileManager,
        ValidatorInterface $validator
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
            ->addArgument('name', InputArgument::REQUIRED, sprintf('The name of the DTO class (e.g. <fg=yellow>%sData</>)', Str::asClassName(Str::getRandomTerm())))
            ->addArgument('bound-class', InputArgument::REQUIRED, 'The name of Entity that the DTO will be bound to')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeDto.txt'))
        ;

        $inputConf->setArgumentAsNonInteractive('bound-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (null === $input->getArgument('bound-class')) {
            $argument = $command->getDefinition()->getArgument('bound-class');

            $entities = $this->doctrineHelper->getEntitiesForAutocomplete();

            $question = new Question($argument->getDescription());
            $question->setValidator(function ($answer) use ($entities) {return Validator::existsOrNull($answer, $entities); });
            $question->setAutocompleterValues($entities);
            $question->setMaxAttempts(3);

            $input->setArgument('bound-class', $io->askQuestion($question));
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $dataClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Form\\Data\\',
            'Data'
        );

        $boundClass = $input->getArgument('bound-class');

        $boundClassDetails = $generator->createClassNameDetails(
            $boundClass,
            'Entity\\'
        );

        // Verify that class is an entity
        if (false === $this->doctrineHelper->isClassAMappedEntity($boundClassDetails->getFullName())) {
            throw new RuntimeCommandException('The bound class is not a valid doctrine entity.');
        }

        /**
         * Get class metadata (used to copy annotations and generate properties).
         *
         * @var ClassMetaData
         */
        $metaData = $this->doctrineHelper->getMetadata($boundClassDetails->getFullName());

        // Get list of fields
        $fields = $metaData->fieldMappings;

        // The result is passed to the template
        $addHelpers = $io->confirm('Add helper extract/fill methods?');
        $omitGettersSetters = $io->confirm('Omit generation of getters/setters?');

        // Filter identifiers from generated fields
        $fields = array_filter($fields, function ($field) use ($metaData) {
            return !$metaData->isIdentifier($field['fieldName']);
        });

        // See, whether there are missing methods
        $missingGettersSetters = false;
        foreach ($fields as $fieldName => $mapping) {
            $fields[$fieldName]['hasSetter'] = $this->entityHasSetter($boundClassDetails->getFullName(), $fieldName);
            $fields[$fieldName]['hasGetter'] = $this->entityHasGetter($boundClassDetails->getFullName(), $fieldName);

            if (!$fields[$fieldName]['hasGetter'] || !$fields[$fieldName]['hasSetter']) {
                $missingGettersSetters = true;
            }
        }

        $boundClassVars = [
            'bounded_full_class_name' => $boundClassDetails->getFullName(),
            'bounded_class_name' => $boundClassDetails->getShortName(),
        ];

        $DTOClassPath = $generator->generateClass(
            $dataClassNameDetails->getFullName(),
            __DIR__.'/../Resources/skeleton/dto/DTO.tpl.php',
            array_merge(
                [
                    'fields' => $fields,
                    'addHelpers' => $addHelpers,
                    'omitGettersSetters' => $omitGettersSetters,
                ],
                $boundClassVars
            )
        );

        $generator->writeChanges();
        $manipulator = $this->createClassManipulator($DTOClassPath, $omitGettersSetters);
        $mappedFields = $this->getMappedFieldsInEntity($metaData);

        // Did we import assert annotations?
        $assertionsImported = false;

        // Are there differences in the validation constraints between metadata (includes annotations, xml, yaml) and annotations?
        $suspectYamlXmlValidations = false;
        $validatorClassMetadata = $this->validator->getMetadataFor($boundClassDetails->getFullName());

        foreach ($fields as $fieldName => $mapping) {
            $annotationReader = new AnnotationReader();

            // Lookup classname for inherited properties
            if (array_key_exists('declared', $mapping)) {
                $fullClassName = $mapping['declared'];
            } else {
                $fullClassName = $boundClassDetails->getFullName();
            }

            // Property Annotations
            $reflectionProperty = new \ReflectionProperty($fullClassName, $fieldName);
            $propertyAnnotations = $annotationReader->getPropertyAnnotations($reflectionProperty);

            // passed to the ClassManipulator
            $comments = [];
            // Count the Constraints for comparison with the Validator
            $constraintCount = 0;

            foreach ($propertyAnnotations as $annotation) {
                // We want to copy the asserts, so look for their interface
                if ($annotation instanceof Constraint) {
                    // Set flag for use in result message
                    $assertionsImported = true;
                    ++$constraintCount;
                    $comments[] = $manipulator->buildAnnotationLine('@Assert\\'.(new \ReflectionClass($annotation))->getShortName(), $this->getAnnotationAsString($annotation));
                }
            }

            // Compare the amount of constraints in annotations with those in the complete validator-metadata for the entity
            if (false === $this->hasAsManyValidations($validatorClassMetadata->getPropertyMetadata($fieldName), $constraintCount)) {
                $suspectYamlXmlValidations = true;
            }

            $manipulator->addEntityField($fieldName, $mapping, $comments);
        }

        $this->fileManager->dumpFile(
            $DTOClassPath,
            $manipulator->getSourceCode()
        );

        $this->writeSuccessMessage($io);

        if (true === $assertionsImported) {
            $io->note([
                'The maker imported assertion annotations.',
                'Consider removing them from the entity or make sure to keep them updated in both places.',
            ]);
        }

        if (true === $suspectYamlXmlValidations) {
            $io->note([
                'The entity possibly uses Yaml/Xml validators.',
                'Make sure to update the validations to include the new DTO class.',
            ]);
        }

        if (true === $missingGettersSetters) {
            $io->note([
                'The maker found missing getters/setters for properties in the entity.',
                'Please review the generated DTO for @todo comments.',
            ]);
        }

        $io->text([
            sprintf('Next: Review the new DTO <info>%s</info>', $DTOClassPath),
            'Then: Create a form for this DTO by running:',
            sprintf('<info>$ php bin/console make:form %s</>', $boundClassDetails->getShortName()),
            sprintf('and enter <info>\\%s</>', $dataClassNameDetails->getFullName()),
            '',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/forms/data_transfer_objects.html</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->requirePHP71();

        $dependencies->addClassDependency(
            Validation::class,
            'validator',
            // add as an optional dependency: the user *probably* wants validation
            false
        );
    }

    private function createClassManipulator(string $classPath, bool $omitGettersSetters = false): DTOClassSourceManipulator
    {
        return new DTOClassSourceManipulator(
            $this->fileManager->getFileContents($classPath),
            // overwrite existing methods
            true,
            // use annotations
            true,
            // use fluent mutators
            true,
            // omit getters setters?
            $omitGettersSetters
        );
    }

    private function getMappedFieldsInEntity(ClassMetadata $classMetadata)
    {
        $targetFields = array_merge(
            array_keys($classMetadata->fieldMappings),
            array_keys($classMetadata->associationMappings)
        );

        return $targetFields;
    }

    private function getAnnotationAsString(Constraint $annotation)
    {
        // We typecast, because array_diff expects arrays and both functions can return null.
        return array_diff((array) get_object_vars($annotation), (array) get_class_vars(\get_class($annotation)));
    }

    private function hasAsManyValidations($propertyMetadata, $constraintCount)
    {
        $metadataConstraintCount = 0;
        foreach ($propertyMetadata as $metadata) {
            if (isset($metadata->constraints)) {
                $metadataConstraintCount = $metadataConstraintCount + \count($metadata->constraints);
            }
        }

        return $metadataConstraintCount == $constraintCount;
    }

    private function entityHasGetter($entityClassName, $propertyName)
    {
        return method_exists($entityClassName, sprintf('get%s', Str::asCamelCase($propertyName)));
    }

    private function entityHasSetter($entityClassName, $propertyName)
    {
        return method_exists($entityClassName, sprintf('set%s', Str::asCamelCase($propertyName)));
    }
}
