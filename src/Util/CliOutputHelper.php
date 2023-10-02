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

/**
 * Tools used to enhance maker command output.
 *
 * For additional context with Symfony CLI EnvVars, see
 * https://github.com/symfony-cli/symfony-cli/pull/231
 *
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class CliOutputHelper
{
    /**
     * EnvVars exposed by Symfony's CLI.
     */
    public const ENV_VERSION = 'SYMFONY_CLI_VERSION';       // Current CLI Version
    public const ENV_BIN_NAME = 'SYMFONY_CLI_BINARY_NAME';  // Name of the binary e.g. "symfony"

    /**
     * Get the correct command prefix based on Symfony CLI usage.
     */
    public static function getCommandPrefix(): string
    {
        $prompt = 'php bin/console';

        $binaryNameEnvVar = getenv(self::ENV_BIN_NAME);

        if (false !== $binaryNameEnvVar && false !== getenv(self::ENV_VERSION)) {
            $prompt = sprintf('%s console', $binaryNameEnvVar);
        }

        return $prompt;
    }
}
