<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Harness\Lint;

use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Paths;
use Symfony\Bundle\MakerBundle\Tests\Harness\Process\ProcessRunner;

/**
 * Style linting of generated Twig templates — ONE batched subprocess per test
 * case instead of one per file. Never a substitute for the render executor.
 */
final class TwigCsLinter
{
    /**
     * @param list<string> $relativeFiles
     */
    public static function lint(string $appDir, array $relativeFiles): void
    {
        if (!$relativeFiles || getenv('MAKER_SKIP_TWIGCS')) {
            return;
        }

        $binary = Paths::rootPath().'/tools/twigcs/vendor/bin/twigcs';
        if (!file_exists($binary)) {
            throw new \RuntimeException('twigcs not found: run "composer upgrade -W --working-dir=tools/twigcs".');
        }

        $command = ['php', $binary, '--config', './tools/twigcs/.twig_cs.dist'];
        foreach ($relativeFiles as $file) {
            $command[] = $appDir.'/'.$file;
        }

        $process = ProcessRunner::run($command, Paths::rootPath(), allowFailure: true, timeout: 120);
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(\sprintf("Generated Twig templates have style violations:\n%s", ProcessRunner::tail($process->getOutput().$process->getErrorOutput())));
        }
    }
}
