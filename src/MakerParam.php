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
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class MakerParam
{
    private $name;
    private $value;
    private $required;
    private $interactive;

    public function __construct(string $name, $value = null, bool $required = true, bool $interactive = true)
    {
        $this->name = $name;
        $this->value = $value;
        $this->required = $required;
        $this->interactive = $interactive;
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
        return null === $this->value || '' === $this->value;
    }

    public function isInteractive(): bool
    {
        return $this->interactive;
    }
}
