<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\Mapping\Column;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeFixture extends AbstractMaker
{
    private $eventRegistry;

    public static function getCommandName(): string
    {
        return 'make:fixture';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new class to load Doctrine fixtures')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeSubscriber.txt'))
        ;
    }

    public function getParameters(InputInterface $input): array
    {
        return [];
    }

    public function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/doctrine/Fixture.tpl.php' => 'src/DataFixtures/AppFixtures.php',
        ];
    }

    public function writeSuccessMessage(array $params, ConsoleStyle $io)
    {
        parent::writeSuccessMessage($params, $io);

        $io->text([
            'Next: Open your new fixtures class and start customizing it.',
            'See <fg=yellow>https://symfony.com/doc/master/bundles/DoctrineFixturesBundle/index.html</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Column::class,
            'orm-pack'
        );
        $dependencies->addClassDependency(
            Fixture::class,
            'orm-fixtures'
        );
    }
}
