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
    private bool $usingBundledPhpCsFixer = true;
    private bool $usingBundledPhpCsFixerConfig = true;

    public function __construct(
        private ?string $phpCsFixerBinaryPath = null,
        private ?string $phpCsFixerConfigPath = null,
    ) {
        $this->setBinary();
        $this->setConfig();
    }

    public function lintFiles(array $templateFilePaths): void
    {
        $phpFiles = [];

        foreach ($templateFilePaths as $filePath) {
            if (str_ends_with($filePath, '.php')) {
                $phpFiles[] = $filePath;
            }
        }

        $this->lintPhpTemplate($phpFiles);
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

    public function getLinterUserMessage(): array
    {
        $message = [];
        $message[0] = 'Linting Generated Files With:'.\PHP_EOL;
        $message[1] = $this->usingBundledPhpCsFixer ?
            'Bundled PHP-CS-Fixer & ' :
            sprintf('System PHP-CS-Fixer (<info>%s</info>) & ', $this->phpCsFixerBinaryPath)

        ;

        $message[1] .= $this->usingBundledPhpCsFixerConfig ?
            'Bundled PHP-CS-Fixer Configuration'.\PHP_EOL :
            sprintf('System PHP-CS-Fixer Configuration (<info>%s</info>)', $this->phpCsFixerConfigPath).\PHP_EOL
        ;

        $message[] = \PHP_EOL;

        return $message;
    }

    private function setBinary(): void
    {
        if (null !== $this->phpCsFixerBinaryPath) {
            $this->checkPathExists($this->phpCsFixerBinaryPath, false);

            $this->usingBundledPhpCsFixer = false;

            return;
        }

        $this->phpCsFixerBinaryPath = \dirname(__DIR__).'/Resources/bin/php-cs-fixer-v3.13.0.phar';
    }

    private function setConfig(): void
    {
        if (null !== $this->phpCsFixerConfigPath) {
            $this->checkPathExists($this->phpCsFixerConfigPath, true);

            $this->usingBundledPhpCsFixerConfig = false;

            return;
        }

        $defaultConfigPath = '.php-cs-fixer.dist.php';

        if (file_exists($defaultConfigPath)) {
            $this->phpCsFixerConfigPath = $defaultConfigPath;

            $this->usingBundledPhpCsFixerConfig = false;

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
