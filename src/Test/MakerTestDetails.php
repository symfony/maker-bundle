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

use Symfony\Bundle\MakerBundle\MakerInterface;

final class MakerTestDetails
{
    private $maker;

    private $inputs;

    private $fixtureFilesPath;

    private $replacements = [];

    private $postMakeCommands = [];

    /**
     * @param MakerInterface $maker
     * @param array          $inputs
     * @return static
     */
    public static function createTest(MakerInterface $maker, array $inputs)
    {
        return new static($maker, $inputs);
    }

    private function __construct(MakerInterface $maker, array $inputs)
    {
        $this->inputs = $inputs;
        $this->maker = $maker;
    }

    public function setFixtureFilesPath(string $fixtureFilesPath): self
    {
        $this->fixtureFilesPath = $fixtureFilesPath;

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
        return (new \ReflectionObject($this->maker))->getShortName().'_'.($this->fixtureFilesPath ? basename($this->fixtureFilesPath) : 'default');
    }

    public function getPostMakeCommands(): array
    {
        return $this->postMakeCommands;
    }

    public function getReplacements(): array
    {
        return $this->replacements;
    }

    public function getMaker(): MakerInterface
    {
        return $this->maker;
    }
}
