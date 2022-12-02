<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Util;

use Symfony\Component\Process\Process;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class TemplateLinter
{
    public function lintPhpTemplate(string|array $templateFilePath): void
    {
        if (\is_string($templateFilePath)) {
            $templateFilePath = [$templateFilePath];
        }

        $fixerPath = \dirname(__DIR__).'/Bin/php-cs-fixer-v3.13.0.phar';
        $configPath = \dirname(__DIR__).'/Resources/config/php-cs-fixer.config.php';

        if (!file_exists($configPath) || !file_exists($fixerPath)) {
            throw new \RuntimeException('WTF');
        }

        foreach ($templateFilePath as $filePath) {
            $process = Process::fromShellCommandline(sprintf('php %s --config=%s --using-cache=no fix %s', $fixerPath, $configPath, $filePath));
            $process->run();
        }
    }
}
