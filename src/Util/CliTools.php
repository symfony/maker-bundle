<?php

namespace Symfony\Bundle\MakerBundle\Util;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 *
 *
 * @TODO Better class name
 */
final class CliTools
{
    // EnvVars exposed by Symfony's CLI
    const ENV_VERSION = 'SYMFONY_CLI_VERSION';
    const ENV_BIN_NAME = 'SYMFONY_CLI_BINARY_NAME';

    /**
     * Get the correct prefix for maker commands based on the cli tool
     * being used to call make:*
     *
     * @TODO Better Description
     */
    public static function getCommandPrefix(): string
    {
        $prompt = 'php bin/console ';

        $env = getenv(self::ENV_VERSION);

        if (!$env) {
            $prompt = 'symfony console ';
        }

        return $prompt;
    }
}
