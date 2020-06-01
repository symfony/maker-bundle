<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle;

/**
 * A collection of arguments for Makers.
 *
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class MakerArgumentCollection implements \IteratorAggregate
{
    private $arguments = [];

    public function createArgument(string $name, string $value, bool $required = true): MakerArgument
    {
        $this->addArgument($argument = new MakerArgument($name, $value, $required));

        return $argument;
    }

    public function addArgument(MakerArgument $argument): void
    {
        if (isset($this->arguments[$argument->getName()])) {
            // @TODO Throw new ex "Argument already exists - use ArgumentCollection->replaceArgument()"
        }

        $this->arguments[$argument->getName()] = $argument;
    }

    public function getArgument(string $name): MakerArgument
    {
        $this->argumentExists($name);

        return $this->arguments[$name];
    }

    public function replaceArgument(MakerArgument $argument): void
    {
        $this->argumentExists($argument->getName());

        $this->arguments[$argument->getName()] = $argument;
    }

    public function removeArgument(string $name): void
    {
        unset($this->arguments[$name]);
    }

    public function setArgumentValue(string $name, $value): void
    {
        $this->argumentExists($name);

        $this->arguments[$name]->setValue($value);
    }

    public function getArgumentValue(string $name)
    {
        $this->argumentExists($name);

        return $this->arguments[$name]->getValue();
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->arguments);
    }

    private function argumentExists(string $name): void
    {
        if (!isset($this->arguments[$name])) {
            // @TODO Throw arg doesnt exist exception
        }
    }
}
