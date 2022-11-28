<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Security\Object;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Security\Object\AuthenticatorType;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
final class AuthenticatorTypeTest extends TestCase
{
    public function testIsNative(): void
    {
        self::assertTrue((new AuthenticatorType('form_login'))->isNative());
        self::assertTrue((new AuthenticatorType('json_login'))->isNative());
        self::assertTrue((new AuthenticatorType('http_basic'))->isNative());
        self::assertTrue((new AuthenticatorType('login_link'))->isNative());
        self::assertTrue((new AuthenticatorType('access_token'))->isNative());
        self::assertTrue((new AuthenticatorType('x509'))->isNative());
        self::assertTrue((new AuthenticatorType('remote_user'))->isNative());
        self::assertFalse((new AuthenticatorType('SomethingCustom'))->isNative());
    }
}
