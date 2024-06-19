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

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
class PhpCompatUtil
{
    public function __construct(private FileManager $fileManager)
    {
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
