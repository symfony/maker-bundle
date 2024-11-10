<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\Sub;

use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\ServiceInterface;

class ServiceA implements ServiceInterface
{
    public function getName(): string
    {
        return 'FOO';
    }

    public function getDefault(string $mode = self::MODE_FOO): ?string
    {
        return null;
    }
}
