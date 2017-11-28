<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class Str
{
    /**
     * Looks for suffixes in strings in a case-insensitive way.
     */
    public static function hasSuffix(string $value, string $suffix): bool
    {
        return 0 === strcasecmp($suffix, substr($value, -strlen($suffix)));
    }

    /**
     * Ensures that the given string ends with the given suffix. If the string
     * already contains the suffix, it's not added twice. It's case-insensitive
     * (e.g. value: 'Foocommand' suffix: 'Command' -> result: 'FooCommand').
     */
    public static function addSuffix(string $value, string $suffix): string
    {
        return self::removeSuffix($value, $suffix).$suffix;
    }

    /**
     * Ensures that the given string doesn't end with the given suffix. If the
     * string contains the suffix multiple times, only the last one is removed.
     * It's case-insensitive (e.g. value: 'Foocommand' suffix: 'Command' -> result: 'Foo'.
     */
    public static function removeSuffix(string $value, string $suffix): string
    {
        return self::hasSuffix($value, $suffix) ? substr($value, 0, -strlen($suffix)) : $value;
    }

    /**
     * Transforms the given string into the format commonly used by PHP classes,
     * (e.g. `app:do_this-and_that` -> `AppDoThisAndThat`) but it doesn't check
     * the validity of the class name.
     */
    public static function asClassName(string $value, string $suffix = ''): string
    {
        $value = trim($value);
        $value = str_replace(['-', '_', '.', ':'], ' ', $value);
        $value = ucwords($value);
        $value = str_replace(' ', '', $value);
        $value = ucfirst($value);
        $value = self::addSuffix($value, $suffix);

        return $value;
    }

    /**
     * Transforms the given string into the format commonly used by Twig variables
     * (e.g. `BlogPostType` -> `blog_post_type`).
     */
    public static function asTwigVariable(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[^a-zA-Z0-9_]/', '_', $value);
        $value = preg_replace('/(?<=\\w)([A-Z])/', '_$1', $value);
        $value = preg_replace('/_{2,}/', '_', $value);
        $value = strtolower($value);

        return $value;
    }

    public static function asRoutePath(string $value): string
    {
        return '/'.str_replace('_', '/', self::asTwigVariable($value));
    }

    public static function asRouteName(string $value): string
    {
        return self::asTwigVariable($value);
    }

    public static function asCommand(string $value): string
    {
        return str_replace('_', '-', self::asTwigVariable($value));
    }

    public static function asEventMethod(string $eventName): string
    {
        return sprintf('on%s', self::asClassName($eventName));
    }

    public static function getRandomTerm(): string
    {
        $adjectives = [
            'tiny',
            'delicious',
            'gentle',
            'agreeable',
            'brave',
            'orange',
            'grumpy',
            'fierce',
            'victorious',
        ];
        $nouns = [
            'elephant',
            'pizza',
            'jellybean',
            'chef',
            'puppy',
            'gnome',
            'kangaroo',
        ];

        return sprintf('%s %s', $adjectives[array_rand($adjectives)], $nouns[array_rand($nouns)]);
    }
}
