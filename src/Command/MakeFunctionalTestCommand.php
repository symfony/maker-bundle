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
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
class MakeFunctionalTestCommand extends AbstractCommand
{
    protected static $defaultName = 'make:functional-test';

    public function configure()
    {
        $this
            ->setDescription('Creates a new functional test class')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the functional test class (e.g. <fg=yellow>DefaultControllerTest</>).')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeFunctionalTest.txt'))
        ;
    }

    protected function getParameters(): array
    {
        $testName = $this->input->getArgument('name');
        if(empty($testName)) {
            throw new RuntimeCommandException("You must provide the name of the test you want to create");
        }

        $testClassName = Str::asClassName($testName, 'Test');
        Validator::validateClassName($testClassName);

        return [
            'test_class_name' => $testClassName,
        ];
    }

    protected function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/test/Functional.php.txt' => 'tests/'.$params['test_class_name'].'.php',
        ];
    }

    protected function getResultMessage(array $params): string
    {
        return sprintf('<fg=blue>%s</> created successfully.', $params['test_class_name']);
    }

    protected function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        $io->text([
            'Next: Open your new test class and start customizing it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/testing.html#functional-tests</>'
        ]);
    }

    protected function configureDependencies(DependencyBuilder $dependencies)
    {
    }
}
