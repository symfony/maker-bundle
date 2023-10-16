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

final class NamespacesHelper
{
    /** @var string[] */
    private $namespaces;

    public function __construct(array $namespaces = null)
    {
        $this->namespaces = $namespaces ?? [];
    }

    public function getCommandNamespace(): string
    {
        return $this->trim($this->namespaces['command'] ?? 'Command\\');
    }

    public function getControllerNamespace(): string
    {
        return $this->trim($this->namespaces['controller'] ?? 'Controller\\');
    }

    public function getEntityNamespace(): string
    {
        return $this->trim($this->namespaces['entity'] ?? 'Entity\\');
    }

    public function getFixturesNamespace(): string
    {
        return $this->trim($this->namespaces['fixtures'] ?? 'DataFixtures\\');
    }

    public function getFormNamespace(): string
    {
        return $this->trim($this->namespaces['form'] ?? 'Form\\');
    }

    public function getFunctionalTestNamespace(): string
    {
        return $this->trim($this->namespaces['functional_test'] ?? 'Tests\\');
    }

    public function getRepositoryNamespace(): string
    {
        return $this->trim($this->namespaces['repository'] ?? 'Repository\\');
    }

    public function getRootNamespace(): string
    {
        return $this->trim($this->namespaces['root'] ?? 'App\\');
    }

    public function getSecurityNamespace(): string
    {
        return $this->trim($this->namespaces['security'] ?? 'Security\\');
    }

    public function getSerializerNamespace(): string
    {
        return $this->trim($this->namespaces['serializer'] ?? 'Serializer\\');
    }

    public function getSubscriberNamespace(): string
    {
        return $this->trim($this->namespaces['subscriber'] ?? 'EventSubscriber\\');
    }

    public function getTwigNamespace(): string
    {
        return $this->trim($this->namespaces['twig'] ?? 'Twig\\');
    }

    public function getUnitTestNamespace(): string
    {
        return $this->trim($this->namespaces['unit_test'] ?? 'Tests\\');
    }

    public function getValidatorNamespace(): string
    {
        return $this->trim($this->namespaces['validator'] ?? 'Validator\\');
    }

    private function trim(string $namespace): string
    {
        return trim($namespace, '\\');
    }
}
