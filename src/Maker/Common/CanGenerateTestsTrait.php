<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker\Common;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
trait CanGenerateTestsTrait
{
    private bool $generateTests = false;

    public function configureCommandWithTestsOption(Command $command): Command
    {
        $testsHelp = file_get_contents(\dirname(__DIR__, 3).'/config/help/_WithTests.txt');
        $help = $command->getHelp()."\n".$testsHelp;

        $command
            ->addOption(name: 'with-tests', mode: InputOption::VALUE_NONE, description: 'Generate PHPUnit Tests')
            ->setHelp($help)
        ;

        return $command;
    }

    public function interactSetGenerateTests(InputInterface $input, ConsoleStyle $io): void
    {
        // Sanity check for maker dev's - End user should never see this.
        if (!$input->hasOption('with-tests')) {
            throw new RuntimeCommandException('Whoops! "--with-tests" option does not exist. Call "addWithTestsOptions()" in the makers "configureCommand().');
        }

        $this->generateTests = $input->getOption('with-tests');

        if (!$this->generateTests) {
            $this->generateTests = $io->confirm('Do you want to generate PHPUnit tests? [Experimental]', false);
        }
    }

    public function shouldGenerateTests(): bool
    {
        return $this->generateTests;
    }
}
