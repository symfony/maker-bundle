<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Harness\Fixture;

use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Paths;

/**
 * THE fixture path convention: tests/fixtures/<maker-dir>/{app,expected,tests},
 * where <maker-dir> derives from the maker's command name
 * (make:command -> make-command). No more five spellings of dirname(__DIR__).
 */
final class FixtureLocator
{
    private string $fixtureDir;

    public function __construct(string $makerCommandName)
    {
        $this->fixtureDir = Paths::fixturesPath().'/'.str_replace(':', '-', $makerCommandName);
    }

    public function path(string $relativePath): string
    {
        $path = $this->fixtureDir.'/'.$relativePath;
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(\sprintf('Fixture "%s" not found (expected at "%s").', $relativePath, $path));
        }

        return $path;
    }

    public function dir(): string
    {
        return $this->fixtureDir;
    }
}
