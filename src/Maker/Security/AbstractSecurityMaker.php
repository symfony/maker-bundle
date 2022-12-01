<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker\Security;

use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Process\Process;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
abstract class AbstractSecurityMaker extends AbstractMaker
{
    public function runFixer(string $templateFilePath): void
    {
        $fixerPath = \dirname(__DIR__, 2).'/bin/php-cs-fixer-v3.13.0.phar';
        $configPath = \dirname(__DIR__, 2).'/Resources/test/.php_cs.test';

        $process = Process::fromShellCommandline(sprintf('php %s --config=%s --using-cache=no fix %s', $fixerPath, $configPath, $templateFilePath));
        $process->run();
    }
}
