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

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Dependency\DependencyManager;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Convenient abstract class for makers.
 */
abstract class AbstractMaker implements MakerInterface
{
    /**
     * @return void
     */
    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
    }

    /**
     * @return void
     */
    protected function writeSuccessMessage(ConsoleStyle $io)
    {
        $io->newLine();
        $io->writeln(' <bg=green;fg=white>          </>');
        $io->writeln(' <bg=green;fg=white> Success! </>');
        $io->writeln(' <bg=green;fg=white>          </>');
        $io->newLine();
    }

    protected function addDependencies(array $dependencies, ?string $message = null): string
    {
        $dependencyBuilder = new DependencyBuilder();

        foreach ($dependencies as $class => $name) {
            $dependencyBuilder->addClassDependency($class, $name);
        }

        return $dependencyBuilder->getMissingPackagesMessage(
            static::getCommandName(),
            $message
        );
    }

    public function configureComposerDependencies(DependencyManager $dependencyManager): void
    {
        // @TODO - method here in abstract prevents BC with signature added to `MakerInterface::class`
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        // @TODO - do we deprecate this method in favor of the one above. then remove in 2.x
        // @TODO - still have plenty of work todo to determine if thats possible or a good idea...
    }
}
