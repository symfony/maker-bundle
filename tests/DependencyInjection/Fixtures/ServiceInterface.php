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

interface ServiceInterface
{
    public const MODE_FOO = 'foo';

    public function getName(): string;

    public function getDefault(string $mode = self::MODE_FOO): ?string;
}
