<?php

declare(strict_types=1);

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
    /** @var string */
    private $command;

    /** @var string */
    private $controller;

    /** @var string */
    private $entity;

    /** @var string */
    private $fixtures;

    /** @var string */
    private $form;

    /** @var string */
    private $functionalTest;

    /** @var string */
    private $repository;

    /** @var string */
    private $root;

    /** @var string */
    private $security;

    /** @var string */
    private $serializer;

    /** @var string */
    private $subscriber;

    /** @var string */
    private $twig;

    /** @var string */
    private $unitTest;

    /** @var string */
    private $validator;

    public function __construct(
        string $root,
        string $command = null,
        string $controller = null,
        string $entity = null,
        string $fixtures = null,
        string $form = null,
        string $functionalTest = null,
        string $repository = null,
        string $security = null,
        string $serializer = null,
        string $subscriber = null,
        string $twig = null,
        string $unitTest = null,
        string $validator = null
    ) {
        $this->root = $root;
        $this->command = $command ?? 'Command\\';
        $this->controller = $controller ?? 'Controller\\';
        $this->entity = $entity ?? 'Entity\\';
        $this->fixtures = $fixtures ?? 'DataFixtures\\';
        $this->form = $form ?? 'Form\\';
        $this->functionalTest = $functionalTest ?? 'Tests\\';
        $this->repository = $repository ?? 'Repository\\';
        $this->security = $security ?? 'Security\\';
        $this->serializer = $serializer ?? 'Serializer\\';
        $this->subscriber = $subscriber ?? 'EventSubscriber\\';
        $this->twig = $twig ?? 'Twig\\';
        $this->unitTest = $unitTest ?? 'Tests\\';
        $this->validator = $validator ?? 'Validator\\';
    }

    /**
     * Get command namespace.
     *
     * @return string
     */
    public function getCommandNamespace(): string
    {
        return $this->trim($this->command);
    }

    /**
     * Get controller namespace.
     *
     * @return string
     */
    public function getControllerNamespace(): string
    {
        return $this->trim($this->controller);
    }

    /**
     * Get entity namespace.
     *
     * @return string
     */
    public function getEntityNamespace(): string
    {
        return $this->trim($this->entity);
    }

    /**
     * Get fixtures namespace.
     *
     * @return string
     */
    public function getFixturesNamespace(): string
    {
        return $this->trim($this->fixtures);
    }

    /**
     * Get form namespace.
     *
     * @return string
     */
    public function getFormNamespace(): string
    {
        return $this->trim($this->form);
    }

    /**
     * Get functional_test namespace.
     *
     * @return string
     */
    public function getFunctionalTestNamespace(): string
    {
        return $this->trim($this->functionalTest);
    }

    /**
     * Get repository namespace.
     *
     * @return string
     */
    public function getRepositoryNamespace(): string
    {
        return $this->trim($this->repository);
    }

    /**
     * Get root namespace.
     *
     * @return string
     */
    public function getRootNamespace(): string
    {
        return $this->trim($this->root);
    }

    /**
     * Get security namespace.
     *
     * @return string
     */
    public function getSecurityNamespace(): string
    {
        return $this->trim($this->security);
    }

    /**
     * Get serializer namespace.
     *
     * @return string
     */
    public function getSerializerNamespace(): string
    {
        return $this->trim($this->serializer);
    }

    /**
     * Get subscriber namespace.
     *
     * @return string
     */
    public function getSubscriberNamespace(): string
    {
        return $this->trim($this->subscriber);
    }

    /**
     * Get twig namespace.
     *
     * @return string
     */
    public function getTwigNamespace(): string
    {
        return $this->trim($this->twig);
    }

    /**
     * Get unit_test namespace.
     *
     * @return string
     */
    public function getUnitTestNamespace(): string
    {
        return $this->trim($this->unitTest);
    }

    /**
     * Get validator namespace.
     *
     * @return string
     */
    public function getValidatorNamespace(): string
    {
        return $this->trim($this->validator);
    }

    /**
     * Trim backslashes in given namespace.
     *
     * @param string $namespace
     *
     * @return string
     */
    private function trim(string $namespace): string
    {
        return trim($namespace, '\\');
    }
}
