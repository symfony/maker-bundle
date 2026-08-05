<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

register_shutdown_function(static function (): void {
    $log = getenv('MAKER_HARNESS_INCLUDE_LOG');
    if (is_string($log) && '' !== $log) {
        @file_put_contents($log, json_encode(get_included_files()));
    }
});
