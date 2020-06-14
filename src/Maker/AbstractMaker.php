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
use Symfony\Bundle\MakerBundle\MakerParam;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Convenient abstract class for makers.
 */
abstract class AbstractMaker implements MakerInterface
{
    /**
     * @var MakerParam[]
     */
    private $makerParams = [];

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
    }

    /**
     * Get the value of a maker param.
     *
     * Formally, $input->getArgument($name);
     *
     * @return mixed
     */
    protected function getMakerParamValue(string $name)
    {
        return $this->makerParams[$name]->getValue();
    }

    /**
     * Replacement for $input->setArgument($name).
     */
    protected function setMakerParam(string $name, $value): void
    {
        $this->makerParams[$name] = new MakerParam($name, $value);
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
