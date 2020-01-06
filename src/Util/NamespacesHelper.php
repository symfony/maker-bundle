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
        return $this->trim($this->namespaces['command_namespace'] ?? 'Command\\');
    }

    public function getControllerNamespace(): string
    {
        return $this->trim($this->namespaces['controller_namespace'] ?? 'Controller\\');
    }

    public function getEntityNamespace(): string
    {
        return $this->trim($this->namespaces['entity_namespace'] ?? 'Entity\\');
    }

    public function getFixturesNamespace(): string
    {
        return $this->trim($this->namespaces['fixtures_namespace'] ?? 'DataFixtures\\');
    }

    public function getFormNamespace(): string
    {
        return $this->trim($this->namespaces['form_namespace'] ?? 'Form\\');
    }

    public function getFunctionalTestNamespace(): string
    {
        return $this->trim($this->namespaces['functional_test_namespace'] ?? 'Tests\\');
    }

    public function getRepositoryNamespace(): string
    {
        return $this->trim($this->namespaces['repository_namespace'] ?? 'Repository\\');
    }

    public function getRootNamespace(): string
    {
        return $this->trim($this->namespaces['root_namespace'] ?? 'App\\');
    }

    public function getSecurityNamespace(): string
    {
        return $this->trim($this->namespaces['security_namespace'] ?? 'Security\\');
    }

    public function getSerializerNamespace(): string
    {
        return $this->trim($this->namespaces['serializer_namespace'] ?? 'Serializer\\');
    }

    public function getSubscriberNamespace(): string
    {
        return $this->trim($this->namespaces['subscriber_namespace'] ?? 'EventSubscriber\\');
    }

    public function getTwigNamespace(): string
    {
        return $this->trim($this->namespaces['twig_namespace'] ?? 'Twig\\');
    }

    public function getUnitTestNamespace(): string
    {
        return $this->trim($this->namespaces['unit_test_namespace'] ?? 'Tests\\');
    }

    public function getValidatorNamespace(): string
    {
        return $this->trim($this->namespaces['validator_namespace'] ?? 'Validator\\');
    }

    private function trim(string $namespace): string
    {
        return trim($namespace, '\\');
    }
}
