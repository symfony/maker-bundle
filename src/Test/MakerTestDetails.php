<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Test;

use Symfony\Bundle\MakerBundle\DependencyBuilder;

final class MakerTestDetails
{
    private $makerClass;

    private $inputs;

    private $fixtureFilesPath;

    private $replacements = [];

    private $preMakeCommands = [];

    private $postMakeCommands = [];

    private $assert;

    private $extraDependencies = [];

    /**
     * @param string $makerClass
     * @param array  $inputs
     *
     * @return static
     */
    public static function createTest(string $makerClass, array $inputs)
    {
        return new static($makerClass, $inputs);
    }

    private function __construct(string $makerClass, array $inputs)
    {
        $this->inputs = $inputs;
        $this->makerClass = $makerClass;
    }

    public function setFixtureFilesPath(string $fixtureFilesPath): self
    {
        $this->fixtureFilesPath = $fixtureFilesPath;

        return $this;
    }

    public function addPreMakeCommand(string $preMakeCommand): self
    {
        $this->preMakeCommands[] = $preMakeCommand;

        return $this;
    }

    public function addPostMakeCommand(string $postMakeCommand): self
    {
        $this->postMakeCommands[] = $postMakeCommand;

        return $this;
    }

    public function addReplacement(string $filename, string $find, string $replace): self
    {
        $this->replacements[] = [
            'filename' => $filename,
            'find' => $find,
            'replace' => $replace,
        ];

        return $this;
    }

    /**
     * Pass a callable that will be called after the maker command has been run.
     *
     *      $test->assert(function(string $output, string $directory) {
     *          // $output is the command output text
     *          // $directory is the directory where the project lives
     *      })
     *
     * @param callable $assert
     *
     * @return MakerTestDetails
     */
    public function assert($assert): self
    {
        $this->assert = $assert;

        return $this;
    }

    public function addExtraDependencies(string $packageName): self
    {
        $this->extraDependencies[] = $packageName;

        return $this;
    }

    public function getInputs(): array
    {
        return $this->inputs;
    }

    public function getFixtureFilesPath()
    {
        return $this->fixtureFilesPath;
    }

    public function getUniqueCacheDirectoryName(): string
    {
        // create a unique directory name for this project
        // but one that will be the same each time the tests are run
        return (new \ReflectionClass($this->makerClass))->getShortName().'_'.($this->fixtureFilesPath ? basename($this->fixtureFilesPath) : 'default').'_'.md5(serialize($this->extraDependencies));
    }

    public function getPreMakeCommands(): array
    {
        return $this->preMakeCommands;
    }

    public function getPostMakeCommands(): array
    {
        return $this->postMakeCommands;
    }

    public function getReplacements(): array
    {
        return $this->replacements;
    }

    public function getMakerClass(): string
    {
        return $this->makerClass;
    }

    /**
     * @return callable
     */
    public function getAssert()
    {
        return $this->assert;
    }

    public function getDependencies()
    {
        $depBuilder = new DependencyBuilder();
        call_user_func([$this->makerClass, 'configureDependencies'], $depBuilder);

        return array_merge($depBuilder->getMissingDependencies(), $this->extraDependencies);
    }
}
