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

/**
 * @internal
 */
final class ClassDetails
{
    private $fullClassName;

    public function __construct(string $fullClassName)
    {
        $this->fullClassName = $fullClassName;
    }

    /**
     * Get list of property names except "id" for use in a make:form context.
     */
    public function getFormFields(): array
    {
        $properties = $this->getProperties();

        return array_diff($properties, ['id']);
    }

    private function getProperties(): array
    {
        $reflect = new \ReflectionClass($this->fullClassName);
        $props = $reflect->getProperties();

        $propertiesList = [];

        foreach ($props as $prop) {
            $propertiesList[] = $prop->getName();
        }

        return $propertiesList;
    }
}
