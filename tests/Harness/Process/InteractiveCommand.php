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

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\InputStream;

/**
 * Expect-style driver for interactive console commands.
 *
 * Prompts are matched by content against the declared expectations, in order.
 * A new, removed or reordered prompt fails loudly with the actual prompt text
 * and the full transcript, instead of silently accepting console defaults,
 * which is how positional input arrays used to break.
 */
final class InteractiveCommand
{
    /**
     * Every SymfonyQuestionHelper prompt ends with a "> " marker line.
     */
    private const PROMPT_MARKER = '/\n\s*>\s?$/';

    /**
     * A detected-but-unmatched prompt is only reported once output has been
     * idle this long; never plain silence, which can be kernel boot or
     * filesystem activity. The hard process timeout stays the global bound.
     */
    private const UNMATCHED_IDLE_SECONDS = 0.3;

    private const DEFAULT_TIMEOUT = 60.0;

    /** @var list<array{fragment: string, answer: string}> */
    private array $expectations = [];

    /** @var list<array{fragment: string, answer: string}> */
    private array $conditionals = [];

    /** @var array<string, string> pattern => reason */
    private array $extraPromptPatterns = [];

    private string $argumentsString = '';
    /** @var array<string, string> */
    private array $extraEnv = [];
    private bool $allowFailure = false;

    /**
     * @param array<string, string>                    $env
     * @param \Closure(string, int, Transcript): mixed $finisher  converts the raw run into the caller's result object
     * @param ?\Closure(): void                        $beforeRun
     */
    public function __construct(
        private string $commandLine,
        private string $cwd,
        private array $env,
        private \Closure $finisher,
        private ?\Closure $beforeRun = null,
    ) {
    }

    public function arguments(string $argumentsString): static
    {
        $this->argumentsString = $argumentsString;

        return $this;
    }

    public function answer(string $promptFragment, string $answer): static
    {
        $this->expectations[] = ['fragment' => $promptFragment, 'answer' => $answer];

        return $this;
    }

    public function acceptDefault(string $promptFragment): static
    {
        return $this->answer($promptFragment, '');
    }

    /**
     * For genuinely conditional prompts (version- or package-dependent) that
     * may or may not appear. Matched only when no ordered expectation does.
     */
    public function answerIfAsked(string $promptFragment, string $answer): static
    {
        $this->conditionals[] = ['fragment' => $promptFragment, 'answer' => $answer];

        return $this;
    }

    /**
     * Accepts the default for prompts matching the given fragments. Scoped and
     * justified on purpose: there is no blanket "ignore all prompts" switch.
     *
     * @param list<string> $patterns
     */
    public function allowExtraPrompts(array $patterns, string $reason): static
    {
        if (!$patterns || '' === trim($reason)) {
            throw new \InvalidArgumentException('allowExtraPrompts() needs at least one pattern and a non-empty reason.');
        }

        foreach ($patterns as $pattern) {
            $this->extraPromptPatterns[$pattern] = $reason;
        }

        return $this;
    }

    /**
     * @param array<string, string> $env
     */
    public function env(array $env): static
    {
        $this->extraEnv = array_merge($this->extraEnv, $env);

        return $this;
    }

    public function allowFailure(): static
    {
        $this->allowFailure = true;

        return $this;
    }

    public function run(): mixed
    {
        if ($this->beforeRun) {
            ($this->beforeRun)();
        }

        $commandLine = trim($this->commandLine.' '.$this->argumentsString);
        $process = ProcessRunner::create(
            $commandLine,
            $this->cwd,
            array_merge(['SHELL_INTERACTIVE' => '1'], $this->env, $this->extraEnv),
            ProcessRunner::resolveTimeout(self::DEFAULT_TIMEOUT),
        );

        $inputStream = new InputStream();
        $process->setInput($inputStream);

        $transcript = new Transcript();
        $expectations = $this->expectations;
        $conditionals = $this->conditionals;

        $process->start();

        $consumedOffset = 0;
        $lastOutputLength = 0;
        $lastChangeAt = microtime(true);

        while (true) {
            $running = $process->isRunning();
            $output = $process->getOutput();

            if (\strlen($output) !== $lastOutputLength) {
                $lastOutputLength = \strlen($output);
                $lastChangeAt = microtime(true);
            }

            // the marker must be in the UNCONSUMED tail: right after an answer
            // the console emits a lone newline, making the full output end in
            // "> \n" which still satisfies the marker regex ($ matches before
            // a trailing newline) and would misread pre-prompt silence as an
            // unexpected empty prompt
            $tail = substr($output, $consumedOffset);
            if ('' !== $tail && preg_match(self::PROMPT_MARKER, $tail)) {
                // match against the CURRENT question block only (after the last
                // blank line), not everything printed since the last answer;
                // otherwise a fragment can fire on informational output above
                // and feed its answer to a different pending prompt
                $answered = $this->tryAnswer(self::currentPromptBlock($tail), $inputStream, $expectations, $conditionals, $transcript);

                if ($answered) {
                    $consumedOffset = \strlen($output);
                } else {
                    // expected-failure runs replicate "no more answers": close
                    // stdin so the console aborts, exactly like the real user
                    // hitting Ctrl-D
                    if ($this->allowFailure && !$expectations) {
                        $inputStream->close();
                        $process->wait();
                        break;
                    }

                    if (microtime(true) - $lastChangeAt >= self::UNMATCHED_IDLE_SECONDS) {
                        $process->stop(0);

                        throw new \RuntimeException(\sprintf("Unexpected prompt from \"%s\":\n\n%s\n\n%s\n\nTranscript so far:\n%s", $commandLine, trim($tail), $expectations ? \sprintf('Expected the next prompt to contain "%s".', $expectations[0]['fragment']) : 'No answers were left — every declared expectation was already used.', $transcript->render()));
                    }
                }
            }

            if (!$running) {
                break;
            }

            try {
                $process->checkTimeout();
            } catch (ProcessTimedOutException $e) {
                throw new \RuntimeException(\sprintf("Command \"%s\" timed out.\n\nTranscript:\n%s\n\n--- output ---\n%s", $commandLine, $transcript->render(), ProcessRunner::tail($process->getOutput())), previous: $e);
            }
            usleep(30_000);
        }

        $inputStream->close();
        $process->wait();
        $output = $process->getOutput();

        if (!$process->isSuccessful() && !$this->allowFailure) {
            throw new \RuntimeException(\sprintf("Command \"%s\" failed (exit %d).\n\nTranscript:\n%s\n\n--- output ---\n%s\n--- stderr ---\n%s", $commandLine, $process->getExitCode(), $transcript->render(), ProcessRunner::tail($output), ProcessRunner::tail($process->getErrorOutput())));
        }

        // a run that failed as expected legitimately leaves answers unused
        if ($expectations && !($this->allowFailure && 0 !== $process->getExitCode())) {
            throw new \RuntimeException(\sprintf("%d answer(s) were never used by \"%s\": \"%s\"\n\nTranscript:\n%s\n\n--- output ---\n%s", \count($expectations), $commandLine, implode(', ', array_map(static fn ($e) => \sprintf('"%s"', $e['fragment']), $expectations)), $transcript->render(), ProcessRunner::tail($output)));
        }

        if (0 !== $process->getExitCode()) {
            // failed commands print their error to stderr; expose it to
            // output assertions on expected-failure runs
            $output .= "\n".$process->getErrorOutput();
        }

        return ($this->finisher)($output, (int) $process->getExitCode(), $transcript);
    }

    /**
     * The question block SymfonyStyle is currently asking: everything after
     * the last blank line of the unconsumed output (prompts are preceded by
     * an empty line; multi-paragraph help text above stays out of matching).
     */
    private static function currentPromptBlock(string $tail): string
    {
        $pos = strrpos(rtrim($tail), "\n\n");

        return false === $pos ? $tail : substr($tail, $pos);
    }

    /**
     * @param array<int, array{fragment: string, answer: string}> $expectations
     * @param array<int, array{fragment: string, answer: string}> $conditionals
     */
    private function tryAnswer(string $prompt, InputStream $inputStream, array &$expectations, array &$conditionals, Transcript $transcript): bool
    {
        if ($expectations && false !== stripos($prompt, $expectations[0]['fragment'])) {
            $expectation = array_shift($expectations);
            $inputStream->write($expectation['answer']."\n");
            $transcript->record($prompt, '' === $expectation['answer'] ? null : $expectation['answer'], \sprintf('answer("%s")', $expectation['fragment']));

            return true;
        }

        foreach ($conditionals as $i => $conditional) {
            if (false !== stripos($prompt, $conditional['fragment'])) {
                unset($conditionals[$i]);
                $conditionals = array_values($conditionals);
                $inputStream->write($conditional['answer']."\n");
                $transcript->record($prompt, '' === $conditional['answer'] ? null : $conditional['answer'], \sprintf('answerIfAsked("%s")', $conditional['fragment']));

                return true;
            }
        }

        foreach ($this->extraPromptPatterns as $pattern => $reason) {
            if (false !== stripos($prompt, $pattern)) {
                $inputStream->write("\n");
                $transcript->record($prompt, null, \sprintf('allowExtraPrompts("%s": %s)', $pattern, $reason));

                return true;
            }
        }

        return false;
    }
}
