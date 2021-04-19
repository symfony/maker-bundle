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

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
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

        // workaround for difficulties parsing linked file paths in appveyor
        if (getenv('MAKER_DISABLE_FILE_LINKS')) {
            return $relativePath;
        }

        $outputFormatterStyle = new OutputFormatterStyle();

        if (method_exists(OutputFormatterStyle::class, 'setHref')) {
            $outputFormatterStyle->setHref($formatted);
        }

        return $outputFormatterStyle->apply($relativePath);
    }
}
