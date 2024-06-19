<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Security\Model;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
enum AuthenticatorType: string
{
    case FORM_LOGIN = 'form_login';
    case JSON_LOGIN = 'json_login';
    case HTTP_BASIC = 'http_basic';
    case LOGIN_LINK = 'login_link';
    case ACCESS_TOKEN = 'access_token';
    case X509 = 'x509';
    case REMOTE_USER = 'remote_user';

    case CUSTOM = 'custom_authenticator';
}
