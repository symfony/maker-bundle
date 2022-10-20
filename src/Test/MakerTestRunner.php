<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Test;

use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class MakerTestRunner
{
    private Filesystem $filesystem;
    private ?MakerTestProcess $executedMakerProcess = null;

    public function __construct(
        private MakerTestEnvironment $environment,
    ) {
        $this->filesystem = new Filesystem();
    }

    public function runMaker(array $inputs, string $argumentsString = '', bool $allowedToFail = false): string
    {
        $this->executedMakerProcess = $this->environment->runMaker($inputs, $argumentsString, $allowedToFail);

        return $this->executedMakerProcess->getOutput();
    }

    /**
     * @return void
     */
    public function copy(string $source, string $destination)
    {
        $path = __DIR__.'/../../tests/fixtures/'.$source;

        if (!file_exists($path)) {
            throw new \Exception(sprintf('Cannot find file "%s"', $path));
        }

        if (is_file($path)) {
            $this->filesystem->copy($path, $this->getPath($destination), true);

            return;
        }

        // handle a directory copy
        $finder = new Finder();
        $finder->in($path)->files();

        foreach ($finder as $file) {
            $this->filesystem->copy($file->getPathname(), $this->getPath($file->getRelativePathname()), true);
        }
    }

    public function renderTemplateFile(string $source, string $destination, array $variables): void
    {
        $twig = new Environment(
            new FilesystemLoader(__DIR__.'/../../tests/fixtures')
        );

        $rendered = $twig->render($source, $variables);

        $this->filesystem->mkdir(\dirname($this->getPath($destination)));
        file_put_contents($this->getPath($destination), $rendered);
    }

    public function getPath(string $filename): string
    {
        return $this->environment->getPath().'/'.$filename;
    }

    public function readYaml(string $filename): array
    {
        return Yaml::parse(file_get_contents($this->getPath($filename)));
    }

    public function getExecutedMakerProcess(): MakerTestProcess
    {
        if (!$this->executedMakerProcess) {
            throw new \Exception('Maker process has not been executed yet.');
        }

        return $this->executedMakerProcess;
    }

    /**
     * @return void
     */
    public function modifyYamlFile(string $filename, \Closure $callback)
    {
        $path = $this->getPath($filename);
        $manipulator = new YamlSourceManipulator(file_get_contents($path));

        $newData = $callback($manipulator->getData());
        if (!\is_array($newData)) {
            throw new \Exception('The modifyYamlFile() callback must return the final array of data');
        }
        $manipulator->setData($newData);

        file_put_contents($path, $manipulator->getContents());
    }

    /**
     * @return void
     */
    public function runConsole(string $command, array $inputs, string $arguments = '')
    {
        $process = $this->environment->createInteractiveCommandProcess(
            $command,
            $inputs,
            $arguments
        );

        $process->run();
    }

    public function runProcess(string $command): void
    {
        MakerTestProcess::create($command, $this->environment->getPath())->run();
    }

    public function replaceInFile(string $filename, string $find, string $replace, bool $allowNotFound = false): void
    {
        $this->environment->processReplacement(
            $this->environment->getPath(),
            $filename,
            $find,
            $replace,
            $allowNotFound
        );
    }

    public function removeFromFile(string $filename, string $find, bool $allowNotFound = false): void
    {
        $this->environment->processReplacement(
            $this->environment->getPath(),
            $filename,
            $find,
            '',
            $allowNotFound
        );
    }

    public function configureDatabase(bool $createSchema = true): void
    {
        $this->replaceInFile(
            '.env',
            'postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=14&charset=utf8',
            getenv('TEST_DATABASE_DSN')
        );

        // Flex includes a recipe to suffix the dbname w/ "_test" - lets keep
        // things simple for these tests and not do that.
        $this->modifyYamlFile('config/packages/doctrine.yaml', function (array $config) {
            if (isset($config['when@test']['doctrine']['dbal']['dbname_suffix'])) {
                unset($config['when@test']['doctrine']['dbal']['dbname_suffix']);
            }

            return $config;
        });

        // @legacy DoctrineBundle 2.4 recipe uses when@test instead of a test/doctrine.yaml config
        if ($this->filesystem->exists('config/packages/test/doctrine.yaml')) {
            $this->removeFromFile(
                'config/packages/test/doctrine.yaml',
                "dbname_suffix: '_test%env(default::TEST_TOKEN)%'"
            );
        }

        // this looks silly, but it's the only way to drop the database *for sure*,
        // as doctrine:database:drop will error if there is no database
        // also, skip for SQLITE, as it does not support --if-not-exists
        if (!str_starts_with(getenv('TEST_DATABASE_DSN'), 'sqlite://')) {
            $this->runConsole('doctrine:database:create', [], '--env=test --if-not-exists');
        }
        $this->runConsole('doctrine:database:drop', [], '--env=test --force');

        $this->runConsole('doctrine:database:create', [], '--env=test');
        if ($createSchema) {
            $this->runConsole('doctrine:schema:create', [], '--env=test');
        }
    }

    public function updateSchema(): void
    {
        $this->runConsole('doctrine:schema:update', [], '--env=test --force');
    }

    public function runTests(): void
    {
        $internalTestProcess = MakerTestProcess::create(
            sprintf('php %s', $this->getPath('/bin/phpunit')),
            $this->environment->getPath())
            ->run(true)
        ;

        if ($internalTestProcess->isSuccessful()) {
            return;
        }

        throw new ExpectationFailedException(sprintf("Error while running the PHPUnit tests *in* the project: \n\n %s \n\n Command Output: %s", $internalTestProcess->getErrorOutput()."\n".$internalTestProcess->getOutput(), $this->getExecutedMakerProcess()->getErrorOutput()."\n".$this->getExecutedMakerProcess()->getOutput()));
    }

    public function writeFile(string $filename, string $contents): void
    {
        $this->filesystem->mkdir(\dirname($this->getPath($filename)));
        file_put_contents($this->getPath($filename), $contents);
    }

    /**
     * @return void
     */
    public function addToAutoloader(string $namespace, string $path)
    {
        $this->replaceInFile(
            'composer.json',
            '"App\\\Tests\\\": "tests/",',
            sprintf('"App\\\Tests\\\": "tests/",'."\n".'            "%s": "%s",', $namespace, $path)
        );

        $this->environment->runCommand('composer dump-autoload');
    }

    public function deleteFile(string $filename): void
    {
        $this->filesystem->remove($this->getPath($filename));
    }

    public function manipulateClass(string $filename, \Closure $callback): void
    {
        $contents = file_get_contents($this->getPath($filename));
        $manipulator = new ClassSourceManipulator(
            sourceCode: $contents,
            overwrite: true,
        );
        $callback($manipulator);

        file_put_contents($this->getPath($filename), $manipulator->getSourceCode());
    }

    public function getSymfonyVersion(): int
    {
        return $this->environment->getSymfonyVersionInApp();
    }

    public function doesClassExist(string $class): bool
    {
        return $this->environment->doesClassExistInApp($class);
    }
}
