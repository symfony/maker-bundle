<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Docker;

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

/**
 * @author  Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class DockerDatabaseServices
{
    /**
     * @throws RuntimeCommandException
     */
    public static function getDatabaseSkeleton(string $name, string $version): array
    {
        switch ($name) {
            case 'mariadb':
                return [
                    'image' => sprintf('mariadb:%s', $version),
                    'environment' => [
                        'MYSQL_ROOT_PASSWORD' => 'password',
                        'MYSQL_DATABASE' => 'main',
                    ],
                ];
            case 'mysql':
                return [
                    'image' => sprintf('mysql:%s', $version),
                    'environment' => [
                        'MYSQL_ROOT_PASSWORD' => 'password',
                        'MYSQL_DATABASE' => 'main',
                    ],
                ];
            case 'postgres':
                return [
                    'image' => sprintf('postgres:%s', $version),
                    'environment' => [
                        'POSTGRES_PASSWORD' => 'main',
                        'POSTGRES_USER' => 'main',
                        'POSTGRES_DB' => 'main',
                    ],
                ];
        }

        self::throwInvalidDatabase($name);
    }

    /**
     * @throws RuntimeCommandException
     */
    public static function getDefaultPorts(string $name): array
    {
        switch ($name) {
            case 'mariadb':
            case 'mysql':
                return ['3306'];
            case 'postgres':
                return ['5432'];
        }

        self::throwInvalidDatabase($name);
    }

    public static function getSuggestedServiceVersion(string $name): string
    {
        if ('postgres' === $name) {
            return 'alpine';
        }

        return 'latest';
    }

    public static function getMissingExtensionName(string $name): ?string
    {
        $driver = match ($name) {
            'mariadb', 'mysql' => 'mysql',
            'postgres' => 'pgsql',
            default => self::throwInvalidDatabase($name),
        };

        if (!\in_array($driver, \PDO::getAvailableDrivers(), true)) {
            return $driver;
        }

        return null;
    }

    /**
     * @throws RuntimeCommandException
     */
    private static function throwInvalidDatabase(string $name): void
    {
        throw new RuntimeCommandException(sprintf('%s is not a valid / supported docker database type.', $name));
    }
}
