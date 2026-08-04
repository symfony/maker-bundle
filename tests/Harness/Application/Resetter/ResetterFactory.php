<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Harness\Application\Resetter;

final class ResetterFactory
{
    private static ?Resetter $resetter = null;

    public static function best(): Resetter
    {
        if (null === self::$resetter) {
            self::$resetter = match (true) {
                RobocopyResetter::isSupported() => new RobocopyResetter(),
                RsyncResetter::isSupported() => new RsyncResetter(),
                default => new FilesystemResetter(),
            };
        }

        return self::$resetter;
    }
}
