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

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeUnitTest implements MakerInterface
{
    private $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public static function getCommandName(): string
    {
        return 'make:unit-test';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new unit test class')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the unit test class (e.g. <fg=yellow>UtilTest</>).')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeUnitTest.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
    }

    public function getParameters(InputInterface $input): array
    {
        list($testPath, $testNamespace, $testClassName) = $this->extractParameters($input->getArgument('name'));

        $testClassName = Str::asClassName($testClassName, 'Test');
        Validator::validateClassName($testClassName);

        return [
            'test_class_name' => $testClassName,
            'test_namespace' => $testNamespace,
            'test_path' => $testPath,
        ];
    }

    public function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/test/Unit.tpl.php' => $params['test_path'].$params['test_class_name'].'.php',
        ];
    }

    public function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        $io->text([
            'Next: Open your new test class and start customizing it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/testing.html#unit-tests</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
    }

    private function extractParameters(string $name): array
    {
        $testPath = 'tests/';
        $testClassName = $name;
        $testNamespace = 'App\Tests';
        if (file_exists($filePath = $this->projectDir.'/'.$name)) {
            $file = new \SplFileInfo($filePath);
            $testClassName = $file->getBasename('.php');
            $path = substr($name, strpos($name, '/') + 1, -strlen($file->getBasename()));
            if ('' !== $path) {
                $testPath .= $path;
                $testNamespace .= '\\'.str_replace('/', '\\', substr($path, 0, -1));
            }
        }

        return [$testPath, $testNamespace, $testClassName];
    }
}
