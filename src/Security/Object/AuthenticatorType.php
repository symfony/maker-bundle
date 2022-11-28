<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Security\Object;

/**
 * To be replaced by enums in 8.1.
 *
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class AuthenticatorType
{
    public const FORM_LOGIN = 'form_login';
    public const JSON_LOGIN = 'json_login';
    public const HTTP_BASIC = 'http_basic';
    public const LOGIN_LINK = 'login_link';
    public const ACCESS_TOKEN = 'access_token';
    public const X509 = 'x509';
    public const REMOTE_USER = 'remote_user';

    private bool $native;

    public function __construct(private string $name)
    {
        $this->native = \in_array($this->name, self::getNativeTypes(), true);
    }

    public function getType(): string
    {
        return $this->name;
    }

    public function isNative(): bool
    {
        return $this->native;
    }

    public static function getNativeTypes(): array
    {
        return (new \ReflectionClass(__CLASS__))->getConstants();
    }

    public function __toString(): string
    {
        return $this->getType();
    }
}
