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

use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
class PhpCompatUtil
{
    /** @var FileManager */
    private $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function canUseAttributes(): bool
    {
        $version = $this->getPhpVersion();

        return version_compare($version, '8alpha', '>=') && Kernel::VERSION_ID >= 50200;
    }

    public function canUseTypedProperties(): bool
    {
        $version = $this->getPhpVersion();

        return version_compare($version, '7.4', '>=');
    }

    protected function getPhpVersion(): string
    {
        $rootDirectory = $this->fileManager->getRootDirectory();

        $composerLockPath = sprintf('%s/composer.lock', $rootDirectory);

        if (!$this->fileManager->fileExists($composerLockPath)) {
            return \PHP_VERSION;
        }

        $lockFileContents = json_decode($this->fileManager->getFileContents($composerLockPath), true);

        if (empty($lockFileContents['platform-overrides']) || empty($lockFileContents['platform-overrides']['php'])) {
            return \PHP_VERSION;
        }

        return $lockFileContents['platform-overrides']['php'];
    }
}
