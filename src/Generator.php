<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle;

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 *
 * @internal
 */
final class Generator
{
    private $fileManager;
    private $io;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function setIO(SymfonyStyle $io)
    {
        $this->io = $io;
        $this->fileManager->setIO($io);
    }

    public function generate(array $parameters, array $files)
    {
        // check if any of the files to be generated already exists
        foreach ($files as $target) {
            if ($this->fileManager->fileExists($target)) {
                throw new RuntimeCommandException(sprintf('The file "%s" can\'t be generated because it already exists.', $target));
            }
        }

        foreach ($files as $fileTemplatePath => $targetPath) {
            $fileContents = $this->fileManager->parseTemplate($fileTemplatePath, $parameters);
            $this->fileManager->dumpFile($targetPath, $fileContents);
        }
    }
}
