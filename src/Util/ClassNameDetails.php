<?php

namespace Symfony\Bundle\MakerBundle\Util;

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;

/**
 * @internal
 */
final class ClassNameDetails
{
    private $fullClassName;

    private $originalRelativeClassName;

    private $suffix;

    public function __construct(string $fullClassName, string $originalRelativeClassName, string $suffix = null)
    {
        $this->fullClassName = $fullClassName;
        $this->originalRelativeClassName = $originalRelativeClassName;
        $this->suffix = $suffix;
    }

    public static function createFromName(string $name, string $namespacePrefix, string $suffix = '', string $validationErrorMessage = '')
    {
        $cleanedName = Str::asClassName($name, $suffix);
        Validator::validateClassName($cleanedName, $validationErrorMessage);

        $className = rtrim($namespacePrefix, '\\') . '\\' . $cleanedName;

        return new self($className, $cleanedName, $suffix);
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
     * For example, the full class name could be:
     *      App\Entity\Admin\User
     *
     * And the relative class name would likely be:
     *
     *     Admin\User
     *
     * @return string
     */
    public function getOriginalName(): string
    {
        return $this->originalRelativeClassName;
    }

    public function getOriginalNameWithoutSuffix(): string
    {
        return str::removeSuffix($this->originalRelativeClassName, $this->suffix);
    }
}
