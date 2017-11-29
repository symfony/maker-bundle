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

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\BrowserKit\Client;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
class MakeFunctionalTest implements MakerInterface
{
    public static function getCommandName(): string
    {
        return 'make:functional-test';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new functional test class')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the functional test class (e.g. <fg=yellow>DefaultControllerTest</>).')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeFunctionalTest.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
    }

    public function getParameters(InputInterface $input): array
    {
        $testClassName = Str::asClassName($input->getArgument('name'), 'Test');
        Validator::validateClassName($testClassName);

        return [
            'test_class_name' => $testClassName,
        ];
    }

    public function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/test/Functional.tpl.php' => 'tests/'.$params['test_class_name'].'.php',
        ];
    }

    public function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        $io->text([
            'Next: Open your new test class and start customizing it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/testing.html#functional-tests</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Client::class,
            'browser-kit'
        );
    }
}
