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
    // Version must match bundled version file name. e.g. php-cs-fixer-v3.49.9.phar
    public const BUNDLED_PHP_CS_FIXER_VERSION = '3.49.0';

    private bool $usingBundledPhpCsFixer = true;
    private bool $usingBundledPhpCsFixerConfig = true;
    private bool $needsPhpCmdPrefix = true;

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

        $ignoreEnv = str_starts_with(strtolower(\PHP_OS), 'win') ? 'set PHP_CS_FIXER_IGNORE_ENV=1& ' : 'PHP_CS_FIXER_IGNORE_ENV=1 ';

        $cmdPrefix = $this->needsPhpCmdPrefix ? 'php ' : '';

        foreach ($templateFilePath as $filePath) {
            Process::fromShellCommandline(sprintf(
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
            sprintf('System PHP-CS-Fixer (<info>%s</info>) & ', $this->phpCsFixerBinaryPath)

        ;

        $fixerMessage .= $this->usingBundledPhpCsFixerConfig ?
            'Bundled PHP-CS-Fixer Configuration' :
            sprintf('System PHP-CS-Fixer Configuration (<info>%s</info>)', $this->phpCsFixerConfigPath)
        ;

        $output->writeln([$fixerMessage, '']); // Empty string so we have an empty line
    }

    private function setBinary(): void
    {
        // Use Bundled PHP-CS-Fixer
        if (null === $this->phpCsFixerBinaryPath) {
            $this->phpCsFixerBinaryPath = sprintf('%s/Resources/bin/php-cs-fixer-v%s.phar', \dirname(__DIR__), self::BUNDLED_PHP_CS_FIXER_VERSION);

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
        throw new RuntimeCommandException(sprintf('The MAKER_PHP_CS_FIXER_BINARY_PATH provided: %s does not exist.', $this->phpCsFixerBinaryPath));
    }

    private function setConfig(): void
    {
        // No config provided, but there is a dist config file in the project dir
        if (null === $this->phpCsFixerConfigPath && file_exists($defaultConfigPath = '.php-cs-fixer.dist.php')) {
            $this->phpCsFixerConfigPath = $defaultConfigPath;

            $this->usingBundledPhpCsFixerConfig = false;

            return;
        }

        // No config provided and no project dist config - use our config
        if (null === $this->phpCsFixerConfigPath) {
            $this->phpCsFixerConfigPath = \dirname(__DIR__).'/Resources/config/php-cs-fixer.config.php';

            return;
        }

        // The config path provided doesn't exist...
        if (!file_exists($this->phpCsFixerConfigPath)) {
            throw new RuntimeCommandException(sprintf('The MAKER_PHP_CS_FIXER_CONFIG_PATH provided: %s does not exist.', $this->phpCsFixerConfigPath));
        }

        $this->usingBundledPhpCsFixerConfig = false;
    }
}
