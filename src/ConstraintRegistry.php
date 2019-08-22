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

use Symfony\Component\Validator\Constraints;

/**
 * @internal
 */
class ConstraintRegistry
{
    public static $constraintsMap = [
        Constraints\All::class,
        Constraints\Bic::class,
        Constraints\Blank::class,
        Constraints\Callback::class,
        Constraints\CardScheme::class,
        Constraints\Choice::class,
        Constraints\Collection::class,
        Constraints\Composite::class,
        Constraints\Count::class,
        Constraints\Country::class,
        Constraints\Currency::class,
        Constraints\Date::class,
        Constraints\DateTime::class,
        Constraints\DivisibleBy::class,
        Constraints\Email::class,
        Constraints\EqualTo::class,
        Constraints\Existence::class,
        Constraints\Expression::class,
        Constraints\File::class,
        Constraints\GreaterThan::class,
        Constraints\GreaterThanOrEqual::class,
        Constraints\GroupSequence::class,
        Constraints\GroupSequenceProvider::class,
        Constraints\Iban::class,
        Constraints\IdenticalTo::class,
        Constraints\Image::class,
        Constraints\Ip::class,
        Constraints\Isbn::class,
        Constraints\IsFalse::class,
        Constraints\IsNull::class,
        Constraints\Issn::class,
        Constraints\IsTrue::class,
        Constraints\Json::class,
        Constraints\Language::class,
        Constraints\Length::class,
        Constraints\LessThan::class,
        Constraints\LessThanOrEqual::class,
        Constraints\Locale::class,
        Constraints\Luhn::class,
        Constraints\Negative::class,
        Constraints\NegativeOrZero::class,
        Constraints\NotBlank::class,
        Constraints\NotCompromisedPassword::class,
        Constraints\NotEqualTo::class,
        Constraints\NotIdenticalTo::class,
        Constraints\NotNull::class,
        Constraints\NumberConstraintTrait::class,
        Constraints\Optional::class,
        Constraints\Positive::class,
        Constraints\PositiveOrZero::class,
        Constraints\Range::class,
        Constraints\Regex::class,
        Constraints\Required::class,
        Constraints\Time::class,
        Constraints\Timezone::class,
        Constraints\Traverse::class,
        Constraints\Type::class,
        Constraints\Unique::class,
        Constraints\Url::class,
        Constraints\Uuid::class,
        Constraints\Valid::class,
    ];

    public static function getValidationConstraintsMap()
    {
        return array_combine(array_map([Str::class, 'getShortClassName'], self::$constraintsMap), self::$constraintsMap);
    }
}
