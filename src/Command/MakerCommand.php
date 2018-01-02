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

use Psr\Container\ContainerInterface;
use Symfony\Bundle\MakerBundle\ApplicationAwareMakerInterface;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\ExtraGenerationMakerInterface;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Application;
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
    private $makerLocator;
    private $makerClass;
    private $generator;
    private $inputConfig;
    /** @var ConsoleStyle */
    private $io;
    private $checkDependencies = true;

    public function __construct(ContainerInterface $makerLocator, string $makerClass, Generator $generator)
    {
        $this->makerLocator = $makerLocator;
        $this->makerClass = $makerClass;
        $this->generator = $generator;
        $this->inputConfig = new InputConfiguration();

        parent::__construct();
    }

    protected function configure()
    {
        // check dependencies as early as possible, before fetching the Maker
        if ($this->checkDependencies) {
            if (!class_exists(Recipe::class)) {
                throw new RuntimeCommandException(sprintf('The generator commands require your app to use Symfony Flex & a Flex directory structure. See https://symfony.com/doc/current/setup/flex.html'));
            }

            $dependencies = new DependencyBuilder();
            call_user_func([$this->makerClass, 'configureDependencies'], $dependencies);
            if ($missingPackages = $dependencies->getMissingDependencies()) {
                throw new RuntimeCommandException(sprintf("Missing package%s: to use the %s command, run: \n\ncomposer require %s", 1 === count($missingPackages) ? '' : 's', $this->getName(), implode(' ', $missingPackages)));
            }
        }

        $this->getMaker()->configureCommand($this, $this->inputConfig);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new ConsoleStyle($input, $output);
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

        $this->getMaker()->interact($input, $this->io, $this);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $maker = $this->getMaker();
        $this->generator->setIO($this->io);
        $params = $maker->getParameters($input);
        $this->generator->generate($params, $maker->getFiles($params));

        if ($maker instanceof ExtraGenerationMakerInterface) {
            $maker->afterGenerate($this->io, $params);
        }

        $maker->writeSuccessMessage($params, $this->io);
    }

    public function setApplication(Application $application = null)
    {
        parent::setApplication($application);

        if ($this->getMaker() instanceof ApplicationAwareMakerInterface) {
            if (null === $application) {
                throw new \RuntimeException('Application cannot be null.');
            }

            $this->getMaker()->setApplication($application);
        }
    }

    /**
     * @internal Used for testing commands
     */
    public function setCheckDependencies(bool $checkDeps)
    {
        $this->checkDependencies = $checkDeps;
    }

    /**
     * @return MakerInterface
     */
    private function getMaker()
    {
        return $this->makerLocator->get('maker');
    }
}
