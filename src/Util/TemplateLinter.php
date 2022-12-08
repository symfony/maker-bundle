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
 * Linters used by make:* commands to cleanup the generated files.
 *
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class TemplateLinter
{
    public function __construct(
        private ?string $phpCsFixerBinaryPath = null,
        private ?string $phpCsFixerConfigPath = null,
    ) {
        $this->setConfig();
        $this->setBinary();
    }

    public function lintPhpTemplate(string|array $templateFilePath): void
    {
        if (\is_string($templateFilePath)) {
            $templateFilePath = [$templateFilePath];
        }

        foreach ($templateFilePath as $filePath) {
            $process = Process::fromShellCommandline(sprintf('php %s --config=%s --using-cache=no fix %s', $this->phpCsFixerBinaryPath, $this->phpCsFixerConfigPath, $filePath));
            $process->run();
        }
    }

    private function setBinary(): void
    {
        if (null !== $this->phpCsFixerBinaryPath) {
            $this->checkPathExists($this->phpCsFixerBinaryPath, false);

            return;
        }

        $defaultBinaryPath = 'bin/php-cs-fixer';

        if (file_exists($defaultBinaryPath)) {
            $this->phpCsFixerBinaryPath = $defaultBinaryPath;

            return;
        }

        $this->phpCsFixerBinaryPath = \dirname(__DIR__).'/Bin/php-cs-fixer-v3.13.0.phar';
    }

    private function setConfig(): void
    {
        if (null !== $this->phpCsFixerConfigPath) {
            $this->checkPathExists($this->phpCsFixerConfigPath, true);

            return;
        }

        $defaultConfigPath = '.php-cs-fixer.dist.php';

        if (file_exists($defaultConfigPath)) {
            $this->phpCsFixerConfigPath = $defaultConfigPath;

            return;
        }

        $this->phpCsFixerConfigPath = \dirname(__DIR__).'/Resources/config/php-cs-fixer.config.php';
    }

    private function checkPathExists(string $path, bool $isConfigPath): void
    {
        if (file_exists($path)) {
            return;
        }

        throw new RuntimeCommandException(sprintf('The %s provided: %s does not exist.', $isConfigPath ? 'MAKER_PHP_CS_FIXER_CONFIG_PATH' : 'MAKER_PHP_CS_FIXER_BINARY_PATH', $path));
    }
}
