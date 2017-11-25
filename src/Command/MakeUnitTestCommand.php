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
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeUnitTestCommand extends AbstractCommand
{
    protected static $defaultName = 'make:unit-test';

    private $projectDir;

    public function __construct(Generator $generator, string $projectDir)
    {
        parent::__construct($generator);

        $this->projectDir = $projectDir;
    }

    public function configure()
    {
        $this
            ->setDescription('Creates a new unit test class')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the unit test class (e.g. <fg=yellow>UtilTest</>).')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeUnitTest.txt'))
        ;
    }

    protected function getParameters(): array
    {
        $name = $this->input->getArgument('name');
        $testNamespace = 'App\Tests';
        $testPath = 'tests/';

        if (is_file($filename = $this->projectDir.'/'.$name)) {
            $file = new \SplFileInfo($filename);
            $testPath .= $path = substr($name, strpos($name, DIRECTORY_SEPARATOR) + 1, -strlen($file->getBasename()));
            if ('' !== $path) {
                $testNamespace .= '\\'.str_replace('/', '\\', substr($path, 0, -1));
            }
            $name = $file->getBasename('.php');
        }

        $testClassName = Str::asClassName($name, 'Test');
        Validator::validateClassName($testClassName);

        return [
            'test_class_name' => $testClassName,
            'test_namespace' => $testNamespace,
            'test_path' => $testPath,
        ];
    }

    protected function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/test/Unit.php.txt' => $params['test_path'].$params['test_class_name'].'.php',
        ];
    }

    protected function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        $io->text([
            'Next: Open your new test class and start customizing it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/testing.html#unit-tests</>'
        ]);
    }

    protected function configureDependencies(DependencyBuilder $dependencies)
    {
        // TODO: Implement configureDependencies() method.
    }
}
