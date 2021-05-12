<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Util;

use Symfony\Bundle\MakerBundle\Str;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class TemplateClassDetails
{
    private $fullClassName;
    private $shortClassName;
    private $isTyped;

    public function __construct(string $fullClassName, bool $isTyped)
    {
        $this->fullClassName = $fullClassName;
        $this->shortClassName = Str::getShortClassName($fullClassName);
        $this->isTyped = $isTyped;
    }

    public function getUseStatement(bool $trailingNewLine = true): string
    {
        $stmt = sprintf('use %s;', $this->fullClassName);

        if ($trailingNewLine) {
            $stmt = sprintf("%s\n", $stmt);
        }

        return $stmt;
    }

    public function getPropertyStatement(bool $trailingNewLine = true, bool $indent = true): string
    {
        if ($this->isTyped) {
            $stmt = sprintf('private %s %s;', $this->shortClassName, $this->getVariable());
        } else {
            $stmt = sprintf('private %s;', $this->getVariable());
        }

        if ($trailingNewLine) {
            $stmt = sprintf("%s\n", $stmt);
        }

        if ($indent) {
            $stmt = sprintf('    %s', $stmt);
        }

        return $stmt;
    }

    public function getConstructorArgument(bool $trailingNewLine = true, bool $indent = true): string
    {
        $stmt = sprintf('$this->%s = %s;', Str::asLowerCamelCase($this->shortClassName), $this->getVariable());

        if ($trailingNewLine) {
            $stmt = sprintf("%s\n", $stmt);
        }

        if ($indent) {
            $stmt = sprintf('        %s', $stmt);
        }

        return $stmt;
    }

    public function getMethodArgument(): string
    {
        return sprintf('%s %s', $this->shortClassName, $this->getVariable());
    }

    public function getProperty(): string
    {
        return sprintf('$this->%s', Str::asLowerCamelCase($this->shortClassName));
    }

    public function getVariable(): string
    {
        return sprintf('$%s', Str::asLowerCamelCase($this->shortClassName));
    }
}
