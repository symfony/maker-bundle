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

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationDiffFilteredOutput implements OutputInterface
{
    private $output;
    private $buffer = '';
    private $previousLineWasRemoved = false;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function write($messages, $newline = false, $options = 0)
    {
        $messages = $this->filterMessages($messages, $newline);

        $this->output->write($messages, $newline, $options);
    }

    public function writeln($messages, $options = 0)
    {
        $messages = $this->filterMessages($messages, true);

        $this->output->writeln($messages, $options);
    }

    public function setVerbosity($level)
    {
        $this->output->setVerbosity($level);
    }

    public function getVerbosity()
    {
        return $this->output->getVerbosity();
    }

    public function isQuiet()
    {
        return $this->output->isQuiet();
    }

    public function isVerbose()
    {
        return $this->output->isVerbose();
    }

    public function isVeryVerbose()
    {
        return $this->output->isVeryVerbose();
    }

    public function isDebug()
    {
        return $this->output->isDebug();
    }

    public function setDecorated($decorated)
    {
        $this->output->setDecorated($decorated);
    }

    public function isDecorated()
    {
        return $this->output->isDecorated();
    }

    public function setFormatter(OutputFormatterInterface $formatter)
    {
        $this->output->setFormatter($formatter);
    }

    public function getFormatter()
    {
        return $this->output->getFormatter();
    }

    public function fetch(): string
    {
        return $this->buffer;
    }

    private function filterMessages($messages, bool $newLine)
    {
        if (!is_iterable($messages)) {
            $messages = [$messages];
        }

        $hiddenPhrases = [
            'Generated new migration class',
            'To run just this migration',
            'To revert the migration you',
        ];

        foreach ($messages as $key => $message) {
            $this->buffer .= $message;

            if ($newLine) {
                $this->buffer .= PHP_EOL;
            }

            if ($this->previousLineWasRemoved && !trim($message)) {
                // hide a blank line after a filtered line
                unset($messages[$key]);
                $this->previousLineWasRemoved = false;

                continue;
            }

            $this->previousLineWasRemoved = false;
            foreach ($hiddenPhrases as $hiddenPhrase) {
                if (false !== strpos($message, $hiddenPhrase)) {
                    $this->previousLineWasRemoved = true;
                    unset($messages[$key]);

                    break;
                }
            }
        }

        return array_values($messages);
    }
}
