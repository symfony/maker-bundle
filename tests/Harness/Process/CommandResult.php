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

use PHPUnit\Framework\Assert;

/**
 * Result of one maker run: CLI output plus the created/updated artifact lists
 * discovered by the before/after filesystem manifest (not by scraping stdout).
 */
final class CommandResult
{
    /**
     * @param list<string> $created
     * @param list<string> $updated
     */
    public function __construct(
        private string $output,
        private int $exitCode,
        private array $created,
        private array $updated,
        private Transcript $transcript,
    ) {
    }

    public function output(): string
    {
        return $this->output;
    }

    public function exitCode(): int
    {
        return $this->exitCode;
    }

    public function transcript(): Transcript
    {
        return $this->transcript;
    }

    /** @return list<string> */
    public function created(): array
    {
        return $this->created;
    }

    /** @return list<string> */
    public function updated(): array
    {
        return $this->updated;
    }

    public function assertCreated(string ...$paths): static
    {
        foreach ($paths as $path) {
            Assert::assertContains($path, $this->created, \sprintf(
                "Expected the maker to create \"%s\".\nCreated: %s\nUpdated: %s",
                $path,
                $this->listOrNone($this->created),
                $this->listOrNone($this->updated),
            ));
        }

        return $this;
    }

    public function assertUpdated(string ...$paths): static
    {
        foreach ($paths as $path) {
            Assert::assertContains($path, $this->updated, \sprintf(
                "Expected the maker to update \"%s\".\nCreated: %s\nUpdated: %s",
                $path,
                $this->listOrNone($this->created),
                $this->listOrNone($this->updated),
            ));
        }

        return $this;
    }

    /**
     * @param list<string> $paths
     */
    public function assertCreatedExactly(array $paths): static
    {
        $expected = $paths;
        $actual = $this->created;
        sort($expected);
        sort($actual);

        Assert::assertSame($expected, $actual, 'The maker did not create exactly the expected set of files.');

        return $this;
    }

    public function assertOutputContains(string $needle): static
    {
        Assert::assertStringContainsString($needle, $this->output);

        return $this;
    }

    /**
     * @param list<string> $paths
     */
    private function listOrNone(array $paths): string
    {
        return $paths ? implode(', ', $paths) : '(none)';
    }
}
