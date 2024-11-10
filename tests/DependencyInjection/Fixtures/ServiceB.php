<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures;

class ServiceB extends ServiceA
{
    public function getName(): string
    {
        return 'service_b';
    }

    public function getFoo(): bool
    {
        return true;
    }

    public static function getStaticValue(string $default = ''): ServiceInterface|string|null
    {
        return 'service_b';
    }
}
