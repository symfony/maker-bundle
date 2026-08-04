<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Harness\Application\Resetter;

interface Resetter
{
    /**
     * Mirrors $from into $to (content sync with deletion of extras),
     * preserving file AND directory mtimes: a warm Symfony container must
     * still consider the restored sources fresh. Symlinks are copied as links.
     *
     * @param list<string> $excludes root-relative paths, e.g. "/vendor/"
     */
    public function syncTree(string $from, string $to, array $excludes = []): void;
}
