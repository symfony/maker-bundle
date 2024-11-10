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

final class ServiceF implements ServiceInterface, OtherServiceInterface
{
    public function getName(): string
    {
        return 'service_f';
    }

    public function getDefault(string $mode = self::MODE_FOO): ?string
    {
        return 'service_f';
    }
}
