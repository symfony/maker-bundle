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

use Symfony\Component\Process\Process;

final class ProcessRunner
{
    /**
     * @param string|list<string>   $command
     * @param array<string, string> $env
     */
    public static function run(string|array $command, string $cwd, array $env = [], ?float $timeout = null, bool $allowFailure = false): Process
    {
        $process = self::create($command, $cwd, $env, $timeout);
        $process->run();

        if (!$allowFailure && !$process->isSuccessful()) {
            throw new \RuntimeException(\sprintf('Command failed (exit %d) in "%s":\n  "%s"\n\n--- stdout ---\n%s\n--- stderr ---\n%s', $process->getExitCode(), $cwd, \is_array($command) ? implode(' ', $command) : $command, self::tail($process->getOutput()), self::tail($process->getErrorOutput())));
        }

        return $process;
    }

    /**
     * @param string|list<string>   $command
     * @param array<string, string> $env
     */
    public static function create(string|array $command, string $cwd, array $env = [], ?float $timeout = null): Process
    {
        $process = \is_array($command)
            ? new Process($command, $cwd, $env)
            : Process::fromShellCommandline($command, $cwd, $env);

        $process->setTimeout(self::resolveTimeout($timeout));

        return $process;
    }

    public static function resolveTimeout(?float $default): ?float
    {
        $override = $_SERVER['MAKER_PROCESS_TIMEOUT'] ?? getenv('MAKER_PROCESS_TIMEOUT') ?: null;
        if (null === $override) {
            return $default;
        }

        return 'null' === $override ? null : (float) $override;
    }

    public static function tail(string $output, int $maxLines = 60): string
    {
        $lines = explode("\n", trim($output));
        if (\count($lines) <= $maxLines) {
            return trim($output);
        }

        return \sprintf("[... %d earlier lines omitted ...]\n%s", \count($lines) - $maxLines, implode("\n", \array_slice($lines, -$maxLines)));
    }
}
