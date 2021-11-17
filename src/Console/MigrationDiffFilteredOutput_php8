<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Console;

use Symfony\Component\Console\Output\OutputInterface;

class MigrationDiffFilteredOutput implements OutputInterface
{
    use BaseMakerMigrationDiffFilteredOuputTrait;

    public function write($messages, bool $newline = false, $options = 0)
    {
        $this->_write($messages, $newline, $options);
    }

    public function writeln($messages, int $options = 0)
    {
        $this->_writeln($messages, $options);
    }

    public function setVerbosity(int $level)
    {
        $this->output->setVerbosity($level);
    }

    public function setDecorated(bool $decorated)
    {
        $this->output->setDecorated($decorated);
    }
}
