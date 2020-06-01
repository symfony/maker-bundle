<?php

namespace Symfony\Bundle\MakerBundle;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class MakerArgument
{
    private $name;
    private $value;
    private $required;

    public function __construct(string $name, $value = null, bool $required = true)
    {
        $this->name = $name;
        $this->value = $value;
        $this->required = $required;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setValue($value): void
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isEmpty(): bool
    {
        return empty($this->value);
    }
}
