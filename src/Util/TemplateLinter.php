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

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Process\Process;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class TemplateLinter
{
    public function __construct(
        private ?string $binaryPath = null,
        private ?string $configPath = null,
    ) {
        if (null === $this->binaryPath) {
            $this->binaryPath = \dirname(__DIR__).'/Bin/php-cs-fixer-v3.13.0.phar';
        }

        if (null === $this->configPath) {
            $this->configPath = \dirname(__DIR__).'/Resources/config/php-cs-fixer.config.php';
        }

        if (!file_exists($this->binaryPath) || !file_exists($this->configPath)) {
            throw new RuntimeCommandException('Either the config or binary for PHP_CS_FIXER does not exist.');
        }
    }

    public function lintPhpTemplate(string|array $templateFilePath): void
    {
        if (\is_string($templateFilePath)) {
            $templateFilePath = [$templateFilePath];
        }

        foreach ($templateFilePath as $filePath) {
            $process = Process::fromShellCommandline(sprintf('php %s --config=%s --using-cache=no fix %s', $this->binaryPath, $this->configPath, $filePath));
            $process->run();
        }
    }
}
