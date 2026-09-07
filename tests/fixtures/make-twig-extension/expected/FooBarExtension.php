<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

final class FooBarExtension
{
    // If your filter generates SAFE HTML, you should add the "isSafe" argument:
    // #[AsTwigFilter(name: 'filter_name', isSafe: ['html'])]
    // Reference: https://twig.symfony.com/doc/3.x/advanced.html#automatic-escaping
    #[AsTwigFilter('filter_name')]
    public function doSomething(string $value): string
    {
        // ...

        return $value;
    }

    #[AsTwigFunction('function_name')]
    public function doSomethingElse(string $value): string
    {
        // ...

        return $value;
    }
}
