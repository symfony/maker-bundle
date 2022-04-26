<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\String\Inflector\EnglishInflector;
use function Symfony\Component\String\u;

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
        return 0 === strcasecmp($suffix, substr($value, -\strlen($suffix)));
    }

    /**
     * Ensures that the given string ends with the given suffix. If the string
     * already contains the suffix, it's not added twice. It's case-insensitive
     * (e.g. value: 'Foocommand' suffix: 'Command' -> result: 'FooCommand').
     */
    public static function addSuffix(string $value, string $suffix): string
    {
        $string = u($value)->ignoreCase();

        return $string->trimSuffix($suffix)->append($suffix);
    }

    /**
     * Ensures that the given string doesn't end with the given suffix. If the
     * string contains the suffix multiple times, only the last one is removed.
     * It's case-insensitive (e.g. value: 'Foocommand' suffix: 'Command' -> result: 'Foo'.
     */
    public static function removeSuffix(string $value, string $suffix): string
    {
        return u($value)->ignoreCase()->trimSuffix($suffix);
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

    public static function asLowerCamelCase(string $str): string
    {
        return u($str)->camel();
    }

    public static function asCamelCase(string $str): string
    {
        return u($str)->camel()->title();
    }

    public static function asRoutePath(string $value): string
    {
        return '/'.str_replace('_', '/', self::asTwigVariable($value));
    }

    public static function asRouteName(string $value): string
    {
        $routeName = self::asTwigVariable($value);

        return str_starts_with($routeName, 'app_') ? $routeName : 'app_'.$routeName;
    }

    public static function asSnakeCase(string $value): string
    {
        return u($value)->snake();
    }

    public static function asCommand(string $value): string
    {
        return str_replace('_', '-', self::asTwigVariable($value));
    }

    public static function asEventMethod(string $eventName): string
    {
        return sprintf('on%s', self::asClassName($eventName));
    }

    public static function getShortClassName(string $fullClassName): string
    {
        if (empty(self::getNamespace($fullClassName))) {
            return $fullClassName;
        }

        return substr($fullClassName, strrpos($fullClassName, '\\') + 1);
    }

    public static function getNamespace(string $fullClassName): string
    {
        return substr($fullClassName, 0, strrpos($fullClassName, '\\'));
    }

    public static function asFilePath(string $value): string
    {
        $value = Container::underscore(trim($value));
        $value = str_replace('\\', '/', $value);

        return $value;
    }

    public static function singularCamelCaseToPluralCamelCase(string $camelCase): string
    {
        $words = explode('_', u($camelCase)->snake());
        $words[\count($words) - 1] = self::pluralize($words[\count($words) - 1]);
        $reSnaked = implode('_', $words);

        return self::asLowerCamelCase($reSnaked);
    }

    public static function pluralCamelCaseToSingular(string $camelCase): string
    {
        $words = explode('_', u($camelCase)->snake());
        $words[\count($words) - 1] = self::singularize($words[\count($words) - 1]);
        $reSnaked = implode('_', $words);

        return self::asLowerCamelCase($reSnaked);
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
            'popsicle',
            'chef',
            'puppy',
            'gnome',
            'kangaroo',
        ];

        return sprintf('%s %s', $adjectives[array_rand($adjectives)], $nouns[array_rand($nouns)]);
    }

    /**
     * Checks if the given name is a valid PHP variable name.
     *
     * @see http://php.net/manual/en/language.variables.basics.php
     *
     * @param $name string
     *
     * @return bool
     */
    public static function isValidPhpVariableName($name)
    {
        return (bool) preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name, $matches);
    }

    public static function areClassesAlphabetical(string $class1, string $class2)
    {
        $arr1 = [$class1, $class2];
        $arr2 = [$class1, $class2];
        sort($arr2);

        return $arr1[0] == $arr2[0];
    }

    public static function asHumanWords(string $variableName): string
    {
        return str_replace('  ', ' ', ucfirst(trim(implode(' ', preg_split('/(?=[A-Z])/', $variableName)))));
    }

    private static function pluralize(string $word): string
    {
        $result = (new EnglishInflector())->pluralize($word);

        return $result[0];
    }

    private static function singularize(string $word): string
    {
        $result = (new EnglishInflector())->singularize($word);

        return $result[0];
    }
}
