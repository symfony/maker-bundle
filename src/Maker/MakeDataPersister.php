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
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @author Imad ZAIRIG <imadzairig@gmail.com>
 *
 * @internal
 */
final class MakeDataPersister extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:api:data-persister';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates a API Platform Data Persister')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the Data Persister class (e.g. <fg=yellow>CustomDataPersister</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeDataPersister.txt'));
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $dataPersisterClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'DataPersister\\',
            'DataPersister'
        );

        $generator->generateClass(
            $dataPersisterClassNameDetails->getFullName(),
            'api/DataPersister.tpl.php'
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
}
