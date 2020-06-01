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
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\MakerArgumentCollection;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Convenient abstract class for makers.
 */
abstract class AbstractMaker implements MakerInterface
{
    protected $arguments;

    public function __construct()
    {
        $this->arguments = new MakerArgumentCollection();
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
    }

    /**
     * Helper to retrieve the value from an argument within the ArgumentCollection.
     */
    protected function getArgumentValue(string $argumentName)
    {
        return $this->arguments->getArgumentValue($argumentName);
    }

    protected function checkRequiredArgumentValues(): void
    {
        foreach ($this->arguments as $argument) {
            if ($argument->isRequired() && $argument->isEmpty()) {
                throw new RuntimeCommandException(sprintf('The %s argument is required, but a value is not set.', $argument->getName()));
            }
        }
    }

    protected function writeSuccessMessage(ConsoleStyle $io)
    {
        $io->newLine();
        $io->writeln(' <bg=green;fg=white>          </>');
        $io->writeln(' <bg=green;fg=white> Success! </>');
        $io->writeln(' <bg=green;fg=white>          </>');
        $io->newLine();
    }

    protected function addDependencies(array $dependencies, string $message = null): string
    {
        $dependencyBuilder = new DependencyBuilder();

        foreach ($dependencies as $class => $name) {
            $dependencyBuilder->addClassDependency($class, $name);
        }

        return $dependencyBuilder->getMissingPackagesMessage(
            $this->getCommandName(),
            $message
        );
    }
}
