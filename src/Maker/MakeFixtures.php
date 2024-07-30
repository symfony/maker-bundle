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

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\Mapping\Column;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeFixtures extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:fixtures';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a new class to load Doctrine fixtures';
    }

    /** @return void */
    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->addArgument('fixtures-class', InputArgument::OPTIONAL, 'The class name of the fixtures to create (e.g. <fg=yellow>AppFixtures</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeFixture.txt'))
        ;
    }

    /** @return void */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $fixturesClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('fixtures-class'),
            'DataFixtures\\'
        );

        $useStatements = new UseStatementGenerator([
            Fixture::class,
            ObjectManager::class,
        ]);

        $generator->generateClass(
            $fixturesClassNameDetails->getFullName(),
            'doctrine/Fixtures.tpl.php',
            [
                'use_statements' => $useStatements,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new fixtures class and start customizing it.',
            \sprintf('Load your fixtures by running: <comment>php %s doctrine:fixtures:load</comment>', $_SERVER['PHP_SELF']),
            'Docs: <fg=yellow>https://symfony.com/doc/current/bundles/DoctrineFixturesBundle/index.html</>',
        ]);
    }

    /** @return void */
    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Column::class,
            'doctrine'
        );
        $dependencies->addClassDependency(
            Fixture::class,
            'orm-fixtures',
            true,
            true
        );
    }
}
