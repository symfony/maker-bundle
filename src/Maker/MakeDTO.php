<?php

namespace Symfony\Bundle\MakerBundle\Maker;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Validator\Validation;
use Symfony\Bundle\MakerBundle\FileManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Yaml\Yaml;

final class MakeDTO extends AbstractMaker
{
    private DoctrineHelper $entityHelper;
    private FileManager $fileManager;
    private EntityManagerInterface  $entityManager;
    private string $projectDir;

    public function __construct(
        DoctrineHelper $entityHelper,
        FileManager $fileManager,
        EntityManagerInterface $entityManager,
        string $projectDir
    ) {
        $this->entityHelper = $entityHelper;
        $this->fileManager = $fileManager;
        $this->entityManager = $entityManager;
        $this->projectDir = $projectDir;
    }

    public static function getCommandName(): string
    {
        return 'make:dto';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates a new DTO class')
            ->addArgument('bound-class', InputArgument::OPTIONAL, 'The name of Entity that the DTO will be bound to')
            ->addOption('regenerate', 'r', InputOption::VALUE_NONE, 'Instead of adding new fields, simply generate the methods (e.g. getter/setter) for existing fields')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Option to create all model, serializer and validator')
            ->addOption('generate-all-options', null, InputOption::VALUE_NONE, 'Option to add getter, setter and method fill and extract. Whitout this option ask for this methods')
            ->setHelp(file_get_contents(__DIR__.'/../Resources'.'/help/MakeDTO.txt'))
        ;

        $inputConfig->setArgumentAsNonInteractive('bound-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (!$input->getOption('all') && null === $input->getArgument('bound-class')) {
            $argument = $command->getDefinition()->getArgument('bound-class');

            $entities = $this->entityHelper->getEntitiesForAutocomplete();

            $question = new Question($argument->getDescription());
            $question->setValidator(function ($answer) use ($entities) {return Validator::existsOrNull($answer, $entities); });
            $question->setAutocompleterValues($entities);
            $question->setMaxAttempts(3);

            $input->setArgument('bound-class', $io->askQuestion($question));
        }
    }

    /**
     * @throws Exception
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $importAllClass = $input->getOption('all');

        $classes = [];
        if($importAllClass){
            $metas = $this->entityManager->getMetadataFactory()->getAllMetadata();
            foreach ($metas as $meta) {
                $classes[] = str_replace('App\\Entity\\', '', $meta->getName());
            }
        }
        else{
            $classes[] = $input->getArgument('bound-class');
        }

        foreach ($classes as $class){
            $dataEntity = $this->getEntityData($generator, $class, 'Entity\\', '');
            $dataClassDto = $this->getEntityData($generator, $class, 'Form\\Model\\', 'Dto');

            $this->createClassDto($dataEntity, $dataClassDto, $generator, $io, $input);
            $this->createSerializer($dataEntity);
            $this->createValidator($dataEntity, $dataClassDto);
        }

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Create your form with this DTO and start using it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/forms.html</>',
        ]);
    }

    private function createValidator(array $dataEntity, array $dataClassDto)
    {
        $arrayFieldsValidator = null;
        foreach ($dataEntity['fields'] as $field){
            if($field['fieldName'] !== 'id'){
                $arrayFieldsValidator[$field['fieldName']] = [
                    'NotBlank' => null,
                ];
            }
        }

        $array = [
            $dataClassDto['fullName'] => [
                'properties' => $arrayFieldsValidator
            ],
        ];

        $path = $this->projectDir.'/config/validator/'.ucfirst($dataClassDto['shortName']).'.yaml';
        $this->createFileYAML($array, $path, 4);
    }

    private function createSerializer(array $dataEntity)
    {
        $arrayFieldsSerializer = null;
        foreach ($dataEntity['fields'] as $field){
            $groups = null;
            if($field['fieldName'] === 'id'){
                $groups['groups'] = ['groupExample1', 'groupExample2'];
            }
            $arrayFieldsSerializer[$field['fieldName']] = $groups;
        }

        $array = [
            $dataEntity['fullName'] => [
                'attributes' => $arrayFieldsSerializer
            ],
        ];

        $path = $this->projectDir.'/config/serializer/Entity/'.ucfirst($dataEntity['shortName']).'.yaml';
        $this->createFileYAML($array, $path, 6);
    }

    private function createFileYAML(array $data, string $path, int $inline = 2)
    {
        $yaml = Yaml::dump($data, $inline, 4, Yaml::DUMP_NULL_AS_TILDE);

        $dir  = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($path, $yaml);
    }

    /**
     * @throws Exception
     */
    private function createClassDto(array $dataEntity, array $dataClassDto, Generator $generator, ConsoleStyle $io, InputInterface $input)
    {
        $optionGenerateWithOption = $input->getOption('generate-all-options');
        $boundClassVars = [
            'bounded_full_class_name' => $dataEntity['fullName'],
            'bounded_class_name' => $dataEntity['shortName'],
        ];

        $fields = array_filter($dataEntity['fields'], function ($field) {
            // mapping includes id field when property is an id
            if (!empty($field['id'])) {
                return false;
            }
            return true;
        });

        if(!$optionGenerateWithOption){
            // the result is passed to the template
            $addHelpers = $io->confirm('Add helper extract/fill methods?');

            // Generate getters/setters
            $addGettersSetters = $io->confirm('Generate getters/setters?');
        }
        else{
            $addHelpers = true;
            $addGettersSetters = true;
        }

        $addImportUid = [];
        if(array_key_exists('uuid', $fields)){
            $addImportUid['addImportUid']['uuid'] = true;
        }elseif(array_key_exists('ulid', $fields)){
            $addImportUid['addImportUid']['ulid'] = true;
        }

        // Skeleton?
        $DTOClassPath = $generator->generateClass(
            $dataClassDto['fullName'],
            __DIR__.'/../Resources'.'/skeleton/form/Data.tpl.php',
            array_merge(
                [
                    'fields' => $fields,
                    'addHelpers' => $addHelpers,
                    'addGettersSetters' => $addGettersSetters
                ],
                $boundClassVars,
                $addImportUid
            )
        );

        $generator->writeChanges();
        $manipulator = $this->createClassManipulator($DTOClassPath, $addGettersSetters);
        $mappedFields = $this->getMappedFieldsInEntity($dataEntity['metaData']);

        foreach ($fields as $fieldName => $mapping) {
            if (!in_array($fieldName, $mappedFields)) {
                continue;
            }

            $manipulator->addEntityField($fieldName, $mapping, [], true);
        }

        $this->fileManager->dumpFile(
            $DTOClassPath,
            $manipulator->getSourceCode()
        );
    }

    /**
     * @param string $boundClass
     * @param string $namespace
     * @param string $suffix
     * @param Generator $generator
     * @return array
     */
    private function getEntityData(Generator $generator, string $boundClass, string $namespace, string $suffix = ''): array
    {
        $boundClassDetails = $generator->createClassNameDetails(
            $boundClass,
            $namespace,
            $suffix
        );

        $fullName = $boundClassDetails->getFullName();
        $shortName = $boundClassDetails->getShortName();

        // get class metadata (used by regenerate)
        $metaData = $this->entityHelper->getMetadata($fullName);
        $fields = $metaData->fieldMappings??null;

        dump($metaData->fieldMappings);
        die();

        // list of data Entity
        return [
            'fullName' => $fullName,
            'shortName' => $shortName,
            'metaData' => $metaData,
            'fields' => $fields,
        ];
    }

    public function configureDependencies(DependencyBuilder $dependencies, InputInterface $input = null)
    {
        $dependencies->addClassDependency(
            Validation::class,
            'validator',
            // add as an optional dependency: the user *probably* wants validation
            false
        );
    }

    private function createClassManipulator(string $classPath, bool $addGettersSetters = true): ClassSourceManipulator
    {
        return new ClassSourceManipulator(
            $this->fileManager->getFileContents($classPath),
            // overwrite existing methods
            true,
            // use annotations
            true,
            // use fluent mutators
            true,
            // add getters setters?
            $addGettersSetters
        );
    }

    private function getMappedFieldsInEntity(ClassMetadata $classMetadata): array
    {
        return array_merge(
            array_keys($classMetadata->fieldMappings),
            array_keys($classMetadata->associationMappings)
        );
    }
}
