<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Harness\Application;

/**
 * Per-artifact-PATH execution ledger. "Execute everything" is enforced
 * mechanically:
 *
 *   required = executable artifacts discovered by ArtifactManifest
 *   declared = paths listed by executor covers:
 *   observed = paths reported by runtime instrumentation
 *
 *   required − declared = ∅   (every artifact has a declared executor)
 *   required − observed = ∅   (every artifact was actually exercised)
 *   declared − manifest = ∅   (no stale or misspelled covers: entries)
 *
 * Each violation fails independently; extra observed paths are harmless.
 */
final class ExecutionProof
{
    public const TYPE_PHP = 'php';
    public const TYPE_PHP_TEST = 'php-test';
    public const TYPE_TWIG = 'twig';
    public const TYPE_JS = 'js';
    public const TYPE_MIGRATION = 'migration';
    public const TYPE_CONFIG = 'config';
    public const TYPE_COMPOSE = 'compose';
    public const TYPE_META = 'meta';

    private const TYPES_REQUIRING_PROOF = [
        self::TYPE_PHP, self::TYPE_PHP_TEST, self::TYPE_TWIG, self::TYPE_JS,
        self::TYPE_MIGRATION, self::TYPE_CONFIG, self::TYPE_COMPOSE,
    ];

    /** @var array<string, string> path => type */
    private array $required = [];

    /** @var array<string, string> path => executor description */
    private array $declared = [];

    /** @var array<string, true> */
    private array $observed = [];

    /**
     * @param list<string> $paths artifacts a maker created or updated
     */
    public function registerArtifacts(array $paths): void
    {
        foreach ($paths as $path) {
            $type = self::classify($path);
            if (\in_array($type, self::TYPES_REQUIRING_PROOF, true)) {
                $this->required[$path] = $type;
            }
        }
    }

    /**
     * @param list<string> $paths
     */
    public function declare(array $paths, string $executor): void
    {
        foreach ($paths as $path) {
            $this->declared[$path] = $executor;
        }
    }

    /**
     * @param list<string> $paths
     */
    public function observe(array $paths): void
    {
        foreach ($paths as $path) {
            $this->observed[$path] = true;
        }
    }

    /**
     * The "validated" distinction lives in the declared-executor label: compose
     * files are the documented exception that is validated, never executed.
     */
    public function recordValidated(string $path): void
    {
        $this->declared[$path] ??= 'docker compose config (documented exception)';
        $this->observed[$path] = true;
    }

    /**
     * Config artifacts are proven by a kernel boot that happens after the
     * mutation, in an env that loads their location — the boot observes them.
     *
     * @return list<string> the required config paths loaded by a boot in $env
     */
    public function configPathsLoadedBy(string $env): array
    {
        $loaded = [];
        foreach ($this->required as $path => $type) {
            if (self::TYPE_CONFIG === $type && self::configPathIsLoadedByEnv($path, $env)) {
                $loaded[] = $path;
            }
        }

        return $loaded;
    }

    /**
     * @return list<string> required paths of the given type
     */
    public function requiredOfType(string $type): array
    {
        return array_keys(array_filter($this->required, static fn ($t) => $t === $type));
    }

    public function check(): void
    {
        $failures = [];

        if ($missingDeclared = array_diff_key($this->required, $this->declared)) {
            $failures[] = \sprintf(
                "Generated artifacts with NO declared executor (add them to a covers: list):\n  %s",
                implode("\n  ", array_map(static fn ($p, $t) => "$p ($t)", array_keys($missingDeclared), $missingDeclared)),
            );
        }

        if ($unobserved = array_diff_key($this->required, $this->observed)) {
            $failures[] = \sprintf(
                "Generated artifacts that were never actually exercised at runtime:\n  %s",
                implode("\n  ", array_map(static fn ($p, $t) => "$p ($t)", array_keys($unobserved), $unobserved)),
            );
        }

        if ($stale = array_diff_key($this->declared, $this->required)) {
            $failures[] = \sprintf(
                "covers: entries that match no generated artifact (stale or misspelled):\n  %s",
                implode("\n  ", array_map(static fn ($p, $e) => "$p (declared by $e)", array_keys($stale), $stale)),
            );
        }

        if ($failures) {
            throw new \RuntimeException("The execution contract was not satisfied.\n\n".implode("\n\n", $failures));
        }
    }

    public static function classify(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $basename = basename($path);
        $extension = strtolower(pathinfo($path, \PATHINFO_EXTENSION));

        if (preg_match('/^(compose|docker-compose)[^\/]*\.ya?ml$/', $basename)) {
            return self::TYPE_COMPOSE;
        }
        if (str_starts_with($path, 'config/') || str_starts_with($basename, '.env')) {
            return self::TYPE_CONFIG;
        }
        if ('php' === $extension) {
            if ('importmap.php' === $basename) {
                return self::TYPE_META;
            }
            if (str_starts_with($path, 'migrations/')) {
                return self::TYPE_MIGRATION;
            }

            return str_starts_with($path, 'tests/') ? self::TYPE_PHP_TEST : self::TYPE_PHP;
        }
        if ('twig' === $extension) {
            return self::TYPE_TWIG;
        }
        if (\in_array($extension, ['js', 'ts', 'jsx', 'tsx', 'mjs'], true)) {
            return self::TYPE_JS;
        }
        if (\in_array($basename, ['composer.json', 'composer.lock', 'symfony.lock', 'importmap.php', '.gitignore'], true)
            || \in_array($extension, ['lock', 'md', 'txt', 'dist'], true)
            || str_starts_with($basename, 'phpunit.')
        ) {
            return self::TYPE_META;
        }

        throw new \RuntimeException(\sprintf('Cannot classify generated artifact "%s" for the execution contract. Teach "%s"::classify() about this file type instead of letting it escape the contract.', $path, self::class));
    }

    private static function configPathIsLoadedByEnv(string $path, string $env): bool
    {
        if (str_starts_with($path, 'config/packages/')) {
            $segments = explode('/', $path);

            // config/packages/*.yaml loads in every env;
            // config/packages/<env>/... only in that env
            return 3 === \count($segments) || $segments[2] === $env;
        }

        // .env, .env.test, config/services.yaml, config/routes*, config/bundles.php ...
        if (str_starts_with($path, '.env.')) {
            return substr($path, 5) === $env || str_starts_with($path, '.env.local');
        }

        return true;
    }
}
