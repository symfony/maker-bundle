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
    public function __construct(private array $namespaces = [])
    {
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

    public function getListenerNamespace(): string
    {
        return $this->trim($this->namespaces['listener'] ?? 'EventListener\\');
    }

    public function getMessageNamespace(): string
    {
        return $this->trim($this->namespaces['message'] ?? 'Message\\');
    }

    public function getMessageHandlerNamespace(): string
    {
        return $this->trim($this->namespaces['message_handler'] ?? 'MessageHandler\\');
    }

    public function getMiddlewareNamespace(): string
    {
        return $this->trim($this->namespaces['middleware'] ?? 'Middleware\\');
    }

    public function getRemoteEventNamespace(): string
    {
        return $this->trim($this->namespaces['remote_event'] ?? 'RemoteEvent\\');
    }

    public function getRepositoryNamespace(): string
    {
        return $this->trim($this->namespaces['repository'] ?? 'Repository\\');
    }

    public function getRootNamespace(): string
    {
        return $this->trim($this->namespaces['root'] ?? 'App\\');
    }

    public function getSchedulerNamespace(): string
    {
        return $this->trim($this->namespaces['scheduler'] ?? 'Scheduler\\');
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

    public function getTestNamespace(): string
    {
        return $this->trim($this->namespaces['test'] ?? 'Tests\\');
    }

    public function getTwigNamespace(): string
    {
        return $this->trim($this->namespaces['twig'] ?? 'Twig\\');
    }

    public function getValidatorNamespace(): string
    {
        return $this->trim($this->namespaces['validator'] ?? 'Validator\\');
    }

    public function getWebhookNamespace(): string
    {
        return $this->trim($this->namespaces['webhook'] ?? 'Webhook\\');
    }

    private function trim(string $namespace): string
    {
        return trim($namespace, '\\');
    }
}
