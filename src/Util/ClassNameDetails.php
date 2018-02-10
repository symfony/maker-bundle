<?php

namespace Symfony\Bundle\MakerBundle\Util;

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;

final class ClassNameDetails
{
    private $fullClassName;

    private $namespacePrefix;

    private $suffix;

    public function __construct(string $fullClassName, string $namespacePrefix, string $suffix = null)
    {
        $this->fullClassName = $fullClassName;
        $this->namespacePrefix = trim($namespacePrefix, '\\');
        $this->suffix = $suffix;
    }

    public function getFullName(): string
    {
        return $this->fullClassName;
    }

    public function getShortName(): string
    {
        return Str::getShortClassName($this->fullClassName);
    }

    /**
     * Returns the original class name the user entered (after
     * being cleaned up).
     *
     * For example, assuming the namespace is App\Entity:
     *      App\Entity\Admin\User => Admin\User
     *
     * @return string
     */
    public function getRelativeName(): string
    {
        return str_replace($this->namespacePrefix.'\\', '', $this->fullClassName);
    }

    public function getRelativeNameWithoutSuffix(): string
    {
        return str::removeSuffix($this->getRelativeName(), $this->suffix);
    }
}
