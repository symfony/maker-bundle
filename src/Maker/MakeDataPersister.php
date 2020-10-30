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

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * @author Imad ZAIRIG <imadzairig@gmail.com>
 *
 * @internal
 */
final class MakeDataPersister extends AbstractMaker
{
    /** @var ResourceNameCollectionFactoryInterface */
    protected $ressourceNameCollectionFactory;
    /** @var ResourceMetadataFactoryInterface */
    protected $ressourceMetaDataFactory;
    protected $resourcesClassNames = [];

    public function __construct(
        DoctrineHelper $doctrineHelper,
        $ressourceNameCollectionFactory = null,
        $ressourceMetaDataFactory = null
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->ressourceNameCollection = $ressourceNameCollectionFactory;
        $this->ressourceMetaDataFactory = $ressourceMetaDataFactory;
    }

    public static function getCommandName(): string
    {
        return 'make:api:data-persister';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates a API Platform Data Persister')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the Data Persister class (e.g. <fg=yellow>CustomDataPersister</>)')
            ->addArgument('resource', InputArgument::OPTIONAL, 'The name of the resource class')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeDataPersister.txt'));
        $inputConfig->setArgumentAsNonInteractive('resource');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (null === $input->getArgument('resource')) {
            $argument = $command->getDefinition()->getArgument('resource');
            $question = $this->createResourceClassQuestion($argument->getDescription());
            $value = $io->askQuestion($question);
            $input->setArgument('resource', $value);
            $doctrineOption = new InputOption('is_doctrine_persister', 'a', InputOption::VALUE_NONE, 'Would you like your persister to call the core Doctrine persister?');
            $command->getDefinition()->addOption($doctrineOption);

            if (\in_array($value, $this->resourcesClassNames) && $this->doctrineHelper->isClassAMappedEntity($this->resourcesClassNames[$value])) {
                $description = $command->getDefinition()->getOption('is_doctrine_persister')->getDescription();
                $question = new ConfirmationQuestion($description, false);
                $value = $io->askQuestion($question);

                $input->setOption('is_doctrine_persister', $value);
            }
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $templateVariables = [];
        $resourceShortName = $input->getArgument('resource');
        $this->resourcesClassNames = array_flip($this->getResources());

        if ($resourceShortName && \in_array($resourceShortName, $this->resourcesClassNames)) {
            $resourceClasseName = $this->resourcesClassNames[$resourceShortName];
            $templateVariables['resource_short_name'] = $resourceShortName;
            $templateVariables['resource_class_name'] = $resourceClasseName;
        }

        if ($input->hasOption('is_doctrine_persister')) {
            $templateVariables['is_doctrine_persister'] = true;
            //configure the service
        }

        $dataPersisterClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'DataPersister\\',
            'DataPersister'
        );

        $generator->generateClass(
            $dataPersisterClassNameDetails->getFullName(),
            'api/DataPersister.tpl.php',
            $templateVariables
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next:',
            sprintf('- Open the <info>%s</info> class and add the code you need', $dataPersisterClassNameDetails->getFullName()),
            'Find the documentation at <fg=yellow>https://api-platform.com/docs/core/data-persisters/#creating-a-custom-data-persister</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            ContextAwareDataPersisterInterface::class,
            'api'
        );
    }

    private function createResourceClassQuestion(string $questionText): Question
    {
        $question = new Question($questionText);
        $question->setAutocompleterValues(array_values($this->getResources()));

        return $question;
    }

    private function getResources(): array
    {
        $collection = $this->ressourceNameCollection->create();
        $resources = [];

        foreach ($collection as $className) {
            $resources[$className] = $this->ressourceMetaDataFactory->create($className)->getShortName();
        }

        return $resources;
    }
}
