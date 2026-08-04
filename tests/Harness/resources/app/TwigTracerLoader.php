<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Harness;

use Twig\Loader\LoaderInterface;
use Twig\Source;

/**
 * Installed into twig-enabled app profiles by the MakerBundle test harness
 * (test env only). Records every template Twig actually loads, so the harness
 * can prove that generated templates were really rendered — covers: declares
 * expected coverage, this records the observed one.
 */
final class TwigTracerLoader implements LoaderInterface
{
    public function __construct(
        private LoaderInterface $inner,
    ) {
    }

    public function getSourceContext(string $name): Source
    {
        $this->record($name);

        return $this->inner->getSourceContext($name);
    }

    public function getCacheKey(string $name): string
    {
        $this->record($name);

        return $this->inner->getCacheKey($name);
    }

    public function isFresh(string $name, int $time): bool
    {
        return $this->inner->isFresh($name, $time);
    }

    public function exists(string $name): bool
    {
        return $this->inner->exists($name);
    }

    private function record(string $name): void
    {
        $log = getenv('MAKER_HARNESS_TWIG_LOG');
        if (!\is_string($log) || '' === $log) {
            return;
        }

        $seen = is_file($log) ? (json_decode(file_get_contents($log), true) ?: []) : [];
        if (!isset($seen[$name])) {
            $seen[$name] = true;
            file_put_contents($log, json_encode($seen));
        }
    }
}
