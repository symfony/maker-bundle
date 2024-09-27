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
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;
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
    private bool $needsPhpCmdPrefix = true;

    public function __construct(
        private FileManager $fileManager,
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

        $isWindows = \defined('PHP_WINDOWS_VERSION_MAJOR');
        $ignoreEnv = $isWindows ? 'set PHP_CS_FIXER_IGNORE_ENV=1& ' : 'PHP_CS_FIXER_IGNORE_ENV=1 ';

        $cmdPrefix = $this->needsPhpCmdPrefix ? 'php ' : '';

        foreach ($templateFilePath as $filePath) {
            Process::fromShellCommandline(\sprintf(
                '%s%s%s --config=%s --using-cache=no fix %s',
                $ignoreEnv,
                $cmdPrefix,
                $this->phpCsFixerBinaryPath,
                $this->phpCsFixerConfigPath,
                $filePath
            ))
                ->run()
            ;
        }
    }

    public function writeLinterMessage(OutputInterface $output): void
    {
        $output->writeln('Linting Generated Files With:');

        $fixerMessage = $this->usingBundledPhpCsFixer ?
            'Bundled PHP-CS-Fixer & ' :
            \sprintf('System PHP-CS-Fixer (<info>%s</info>) & ', $this->phpCsFixerBinaryPath)

        ;

        $fixerMessage .= $this->usingBundledPhpCsFixerConfig ?
            'Bundled PHP-CS-Fixer Configuration' :
            \sprintf('System PHP-CS-Fixer Configuration (<info>%s</info>)', $this->phpCsFixerConfigPath)
        ;

        $output->writeln([$fixerMessage, '']); // Empty string so we have an empty line
    }

    private function setBinary(): void
    {
        // Use Bundled (shim) PHP-CS-Fixer
        if (null === $this->phpCsFixerBinaryPath) {
            $shimLocation = \sprintf('%s/vendor/bin/php-cs-fixer', \dirname(__DIR__, 2));

            if (is_file($shimLocation)) {
                $this->phpCsFixerBinaryPath = $shimLocation;

                return;
            }

            return;
        }

        // Path to PHP-CS-Fixer provided
        if (is_file($this->phpCsFixerBinaryPath)) {
            $this->usingBundledPhpCsFixer = false;

            return;
        }

        // PHP-CS-Fixer in the system path?
        if (null !== $path = (new ExecutableFinder())->find($this->phpCsFixerBinaryPath)) {
            $this->phpCsFixerBinaryPath = $path;

            $this->needsPhpCmdPrefix = false;
            $this->usingBundledPhpCsFixer = false;

            return;
        }

        // PHP-CS-Fixer provided is not a file and is not in the system path.
        throw new RuntimeCommandException(\sprintf('The MAKER_PHP_CS_FIXER_BINARY_PATH provided: %s does not exist.', $this->phpCsFixerBinaryPath));
    }

    private function setConfig(): void
    {
        // No config provided, but there is a dist config file in the project dir
        $defaultConfigPath = \sprintf('%s/.php-cs-fixer.dist.php', $this->fileManager->getRootDirectory());
        if (null === $this->phpCsFixerConfigPath && file_exists($defaultConfigPath)) {
            $this->phpCsFixerConfigPath = $defaultConfigPath;

            $this->usingBundledPhpCsFixerConfig = false;

            return;
        }

        // No config provided and no project dist config - use our config
        if (null === $this->phpCsFixerConfigPath) {
            $this->phpCsFixerConfigPath = \sprintf('%s/config/php-cs-fixer.config.php', \dirname(__DIR__, 2));

            return;
        }

        // The config path provided doesn't exist...
        if (!file_exists($this->phpCsFixerConfigPath)) {
            throw new RuntimeCommandException(\sprintf('The MAKER_PHP_CS_FIXER_CONFIG_PATH provided: %s does not exist.', $this->phpCsFixerConfigPath));
        }

        $this->usingBundledPhpCsFixerConfig = false;
    }
}
