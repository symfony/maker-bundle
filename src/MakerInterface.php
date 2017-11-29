<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Interface that all maker commands must implement.
 *
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
interface MakerInterface
{
    /**
     * Return the command name for your maker (e.g. make:report).
     *
     * @return string
     */
    public static function getCommandName(): string;

    /**
     * Configure the command: set description, input arguments, options, etc.
     *
     * By default, all arguments will be asked interactively. If you want
     * to avoid that, use the $inputConfig->setArgumentAsNonInteractive() method.
     *
     * @param Command            $command
     * @param InputConfiguration $inputConfig
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig);

    /**
     * Configure any library dependencies that your maker requires.
     *
     * @param DependencyBuilder $dependencies
     */
    public function configureDependencies(DependencyBuilder $dependencies);

    /**
     * If necessary, you can use this method to interactively ask the user for input.
     *
     * @param InputInterface $input
     * @param ConsoleStyle   $io
     * @param Command        $command
     */
    public function interact(InputInterface $input, ConsoleStyle $io, Command $command);

    /**
     * Return an array of variables that will be made available to the
     * template files returned from getFiles().
     *
     * @param InputInterface $input
     *
     * @return array
     */
    public function getParameters(InputInterface $input): array;

    /**
     * Return the array of files that should be generated into the user's project.
     *
     * For example:
     *
     *    return array(
     *        __DIR__.'/../Resources/skeleton/command/Command.tpl.php' => 'src/Command/'.$params['command_class_name'].'.php',
     *    );
     *
     * These files are parsed as PHP.
     *
     * @param array $params The parameters returned from getParameters()
     *
     * @return array
     */
    public function getFiles(array $params): array;

    /**
     * An opportunity to write a nice message after generation finishes.
     *
     * @param array        $params
     * @param ConsoleStyle $io
     */
    public function writeNextStepsMessage(array $params, ConsoleStyle $io);
}
