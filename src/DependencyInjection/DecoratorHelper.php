<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\DependencyInjection;

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

/**
 * @author Benjamin Georgeault <git@wedgesama.fr>
 *
 * @internal
 */
final class DecoratorHelper
{
    /**
     * @param array<string>           $ids
     * @param array<string, string>   $serviceClasses
     * @param array<string, string[]> $shortNameMap
     */
    public function __construct(
        private readonly array $ids,
        private readonly array $serviceClasses,
        private readonly array $shortNameMap,
    ) {
    }

    public function suggestIds(): array
    {
        return [
            ...array_keys($this->shortNameMap),
            ...$this->ids,
        ];
    }

    public function getRealId(string $id): ?string
    {
        if (\in_array($id, $this->ids)) {
            return $id;
        }

        if (\array_key_exists($id, $this->shortNameMap) && 1 === \count($this->shortNameMap[$id])) {
            return $this->shortNameMap[$id][0];
        }

        return null;
    }

    public function guessRealIds(string $id): array
    {
        $guessTypos = [];
        foreach ($this->shortNameMap as $shortName => $ids) {
            if (levenshtein($id, $shortName) < 3) {
                $guessTypos = [
                    ...$guessTypos,
                    ...$ids,
                ];
            }
        }

        foreach ($this->ids as $suggestId) {
            if (levenshtein($id, $suggestId) < 3) {
                $guessTypos[] = $suggestId;
            }
        }

        return $guessTypos;
    }

    /**
     * @return class-string
     */
    public function getClass(string $id): string
    {
        if (class_exists($id) || interface_exists($id)) {
            return $id;
        }

        if (\array_key_exists($id, $this->serviceClasses)) {
            return $this->serviceClasses[$id];
        }

        throw new RuntimeCommandException(\sprintf('Cannot getClass for id "%s".', $id));
    }
}
