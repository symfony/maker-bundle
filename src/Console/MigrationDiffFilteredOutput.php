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
    private string $buffer = '';
    private bool $previousLineWasRemoved = false;

    public function __construct(
        private OutputInterface $output,
    ) {
    }

    public function write($messages, bool $newline = false, $options = 0): void
    {
        $messages = $this->filterMessages($messages, $newline);

        $this->output->write($messages, $newline, $options);
    }

    public function writeln($messages, int $options = 0): void
    {
        $messages = $this->filterMessages($messages, true);

        $this->output->writeln($messages, $options);
    }

    public function setVerbosity(int $level): void
    {
        $this->output->setVerbosity($level);
    }

    public function setDecorated(bool $decorated): void
    {
        $this->output->setDecorated($decorated);
    }

    public function getVerbosity(): int
    {
        return $this->output->getVerbosity();
    }

    public function isQuiet(): bool
    {
        return $this->output->isQuiet();
    }

    public function isVerbose(): bool
    {
        return $this->output->isVerbose();
    }

    public function isVeryVerbose(): bool
    {
        return $this->output->isVeryVerbose();
    }

    public function isDebug(): bool
    {
        return $this->output->isDebug();
    }

    public function isDecorated(): bool
    {
        return $this->output->isDecorated();
    }

    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        $this->output->setFormatter($formatter);
    }

    public function getFormatter(): OutputFormatterInterface
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
                $this->buffer .= \PHP_EOL;
            }

            if ($this->previousLineWasRemoved && !trim($message)) {
                // hide a blank line after a filtered line
                unset($messages[$key]);
                $this->previousLineWasRemoved = false;

                continue;
            }

            $this->previousLineWasRemoved = false;
            foreach ($hiddenPhrases as $hiddenPhrase) {
                if (str_contains($message, $hiddenPhrase)) {
                    $this->previousLineWasRemoved = true;
                    unset($messages[$key]);

                    break;
                }
            }
        }

        return array_values($messages);
    }
}
