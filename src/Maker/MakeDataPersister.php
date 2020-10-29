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
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @author Imad ZAIRIG <imadzairig@gmail.com>
 *
 * @internal
 */
final class MakeDataPersister extends AbstractMaker
{
    protected $ressourceNameCollectionFactory;
    protected $ressourceMetaDataFactory;

    public function __construct(
        ResourceNameCollectionFactoryInterface $ressourceNameCollectionFactory,
        ResourceMetadataFactoryInterface $ressourceMetaDataFactory
    ) {
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
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (null === $input->getArgument('resource')) {
            $argument = $command->getDefinition()->getArgument('resource');
            $question = $this->createResourceClassQuestion($argument->getDescription());
            $value = $io->askQuestion($question);
            $input->setArgument('resource', $value);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $resourcesClassNames = array_flip($this->getResources());
        $resourceShortName = $input->getArgument('resource');
        $templateVariables = [];

        if ($resourceShortName) {
            $templateVariables['resource_short_name'] = $resourceShortName;
            $templateVariables['resource_class_name'] = $resourcesClassNames[$resourceShortName];
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
