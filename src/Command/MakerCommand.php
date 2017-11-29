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

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Flex\Recipe;

/**
 * Used as the Command class for the makers.
 *
 * @internal
 */
final class MakerCommand extends Command
{
    private $maker;
    private $generator;
    private $inputConfig;
    /** @var ConsoleStyle */
    private $io;
    private $checkDependencies = true;

    public function __construct(MakerInterface $maker, Generator $generator)
    {
        $this->maker = $maker;
        $this->generator = $generator;
        $this->inputConfig = new InputConfiguration();

        parent::__construct();
    }

    protected function configure()
    {
        $this->maker->configureCommand($this, $this->inputConfig);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new ConsoleStyle($input, $output);

        if ($this->checkDependencies) {
            if (!class_exists(Recipe::class)) {
                throw new RuntimeCommandException(sprintf('The generator commands require your app to use Symfony Flex & a Flex directory structure. See https://symfony.com/doc/current/setup/flex.html'));
            }

            $dependencies = new DependencyBuilder();
            $this->maker->configureDependencies($dependencies);
            if ($missingPackages = $dependencies->getMissingDependencies()) {
                throw new RuntimeCommandException(sprintf("Missing package%s: to use the %s command, run: \n\ncomposer require %s", 1 === count($missingPackages) ? '' : 's', $this->getName(), implode(' ', $missingPackages)));
            }
        }
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->getDefinition()->getArguments() as $argument) {
            if ($input->getArgument($argument->getName())) {
                continue;
            }

            if (in_array($argument->getName(), $this->inputConfig->getNonInteractiveArguments(), true)) {
                continue;
            }

            $value = $this->io->ask($argument->getDescription(), $argument->getDefault(), [Validator::class, 'notBlank']);
            $input->setArgument($argument->getName(), $value);
        }

        $this->maker->interact($input, $this->io, $this);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->generator->setIO($this->io);
        $params = $this->maker->getParameters($input);
        $this->generator->generate($params, $this->maker->getFiles($params));

        $this->io->newLine();
        $this->io->writeln(' <bg=green;fg=white>          </>');
        $this->io->writeln(' <bg=green;fg=white> Success! </>');
        $this->io->writeln(' <bg=green;fg=white>          </>');
        $this->io->newLine();

        $this->maker->writeNextStepsMessage($params, $this->io);
    }

    /**
     * @internal Used for testing commands
     */
    public function setCheckDependencies(bool $checkDeps)
    {
        $this->checkDependencies = $checkDeps;
    }
}
