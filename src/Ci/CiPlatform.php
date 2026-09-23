<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Ci;

/**
 * @internal
 */
enum CiPlatform: string
{
    case GitHubActions = 'GitHub Actions';
    case GitLabCi = 'GitLab CI/CD';

    /**
     * @return list<string>
     */
    public static function labels(): array
    {
        return array_column(self::cases(), 'value');
    }
}
