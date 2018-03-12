<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Doctrine;

/**
 * @author Sadicov Vladimir <sadikoff@gmail.com>
 *
 * @internal
 */
final class DoctrineEntityDetails
{
    private $repositoryClass;

    private $identifier;
    private $displayFields;
    private $formFields;

    public function __construct($repositoryClass, $identifier, $displayFields, $formFields)
    {
        $this->repositoryClass = $repositoryClass;
        $this->identifier = $identifier;
        $this->displayFields = $displayFields;
        $this->formFields = $formFields;
    }

    public function getRepositoryClass()
    {
        return $this->repositoryClass;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getDisplayFields()
    {
        return $this->displayFields;
    }

    public function getFormFields()
    {
        return $this->formFields;
    }
}
