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

use Symfony\Component\HttpKernel\Debug\FileLinkFormatter;

/**
 * @internal
 */
final class MakerFileLinkFormatter
{
    private $fileLinkFormatter;

    public function __construct(FileLinkFormatter $fileLinkFormatter = null)
    {
        // Since nullable types are not available in 7.0; can be removed when php >= 7.1 required
        if (0 == \func_num_args()) {
            throw new \LogicException('$fileLinkFormatter argument is required');
        }

        $this->fileLinkFormatter = $fileLinkFormatter;
    }

    public function makeLinkedPath(string $absolutePath, string $relativePath): string
    {
        if (!$this->fileLinkFormatter) {
            return $relativePath;
        }

        if (!$formatted = $this->fileLinkFormatter->format($absolutePath, 1)) {
            return $relativePath;
        }

        return $this->createLink(
            $relativePath,
            $formatted
        );
    }

    private function createLink(string $text, string $href): string
    {
        return "\033]8;;{$href}\033\\{$text}\033]8;;\033\\";
    }
}
