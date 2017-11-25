<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Command;

use Doctrine\ORM\Mapping\Column;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeEntityCommand extends AbstractCommand
{
    protected static $defaultName = 'make:entity';

    public function configure()
    {
        $this
            ->setDescription('Creates a new Doctrine entity class')
            ->addArgument('entity-class', InputArgument::OPTIONAL, sprintf('The class name of the entity to create (e.g. <fg=yellow>%s</>)', Str::asClassName(Str::getRandomTerm())))
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeEntity.txt'))
        ;
    }

    protected function getParameters(): array
    {
        $entityClassName = Str::asClassName($this->input->getArgument('entity-class'));
        Validator::validateClassName($entityClassName);
        $entityAlias = strtolower($entityClassName[0]);
        $repositoryClassName = Str::addSuffix($entityClassName, 'Repository');

        return [
            'entity_class_name' => $entityClassName,
            'entity_alias' => $entityAlias,
            'repository_class_name' => $repositoryClassName,
        ];
    }

    protected function getFiles(array $params): array
    {
        return [
            'doctrine/Entity.php.txt' => 'src/Entity/'.$params['entity_class_name'].'.php',
            'doctrine/Repository.php.txt' => 'src/Repository/'.$params['repository_class_name'].'.php',
        ];
    }

    protected function getResultMessage(array $params): string
    {
        return sprintf('<fg=blue>%s</> entity and <fg=blue>%s</> created successfully.', $params['entity_class_name'], $params['repository_class_name']);
    }

    protected function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        $io->text([
            'Next: Add more fields to your entity and start using it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/doctrine.html#creating-an-entity-class</>'
        ]);
    }

    protected function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Column::class,
            'orm'
        );
    }
}
