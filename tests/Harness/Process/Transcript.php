<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Harness\Process;

/**
 * Prompt/answer log of one interactive command, rendered into failure messages
 * so an unexpected prompt is diagnosable without re-running anything.
 */
final class Transcript
{
    /** @var list<array{prompt: string, answer: ?string, matched: string}> */
    private array $entries = [];

    public function record(string $prompt, ?string $answer, string $matchedBy): void
    {
        $this->entries[] = ['prompt' => self::condense($prompt), 'answer' => $answer, 'matched' => $matchedBy];
    }

    public function render(): string
    {
        if (!$this->entries) {
            return '(no prompts were answered)';
        }

        $out = [];
        foreach ($this->entries as $i => $entry) {
            $out[] = \sprintf(
                "%2d. prompt:  %s\n    answered: %s  (%s)",
                $i + 1,
                $entry['prompt'],
                null === $entry['answer'] ? '<default>' : var_export($entry['answer'], true),
                $entry['matched'],
            );
        }

        return implode("\n", $out);
    }

    private static function condense(string $prompt): string
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $prompt)), static fn ($l) => '' !== $l && '>' !== $l));
        $text = implode(' | ', \array_slice($lines, -3));

        return \strlen($text) > 220 ? '…'.substr($text, -220) : $text;
    }
}
