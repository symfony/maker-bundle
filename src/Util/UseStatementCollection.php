<?php

namespace Symfony\Bundle\MakerBundle\Util;


/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class UseStatementCollection implements \ArrayAccess, \IteratorAggregate
{
    private $useStatements;

    /**
     * @param string[]|array<string, string> $useStatements
     */
    public function __construct(array $useStatements)
    {
        $this->useStatements = $useStatements;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->useStatements);
    }

    /**
     * @param string|string[]|array<string, string> $className
     */
    public function addUseStatement($className): void
    {
        if (is_array($className)) {
            $this->useStatements = array_merge($this->useStatements, $className);

            return;
        }


        // Let's not add the class again if it already exists.
        if (in_array($className, $this->useStatements, true)) {
            return;
        }

        $this->useStatements[] = $className;
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->useStatements);
    }

    /**
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->useStatements[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->useStatements[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->useStatements[$offset]);
    }
}
