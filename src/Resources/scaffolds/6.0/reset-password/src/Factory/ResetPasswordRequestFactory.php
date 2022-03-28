<?php

namespace App\Factory;

use App\Entity\ResetPasswordRequest;
use App\Repository\ResetPasswordRequestRepository;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<ResetPasswordRequest>
 *
 * @method static ResetPasswordRequest|Proxy createOne(array $attributes = [])
 * @method static ResetPasswordRequest[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static ResetPasswordRequest|Proxy find(object|array|mixed $criteria)
 * @method static ResetPasswordRequest|Proxy findOrCreate(array $attributes)
 * @method static ResetPasswordRequest|Proxy first(string $sortedField = 'id')
 * @method static ResetPasswordRequest|Proxy last(string $sortedField = 'id')
 * @method static ResetPasswordRequest|Proxy random(array $attributes = [])
 * @method static ResetPasswordRequest|Proxy randomOrCreate(array $attributes = [])
 * @method static ResetPasswordRequest[]|Proxy[] all()
 * @method static ResetPasswordRequest[]|Proxy[] findBy(array $attributes)
 * @method static ResetPasswordRequest[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static ResetPasswordRequest[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static ResetPasswordRequestRepository|RepositoryProxy repository()
 * @method ResetPasswordRequest|Proxy create(array|callable $attributes = [])
 */
final class ResetPasswordRequestFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [];
    }

    protected static function getClass(): string
    {
        return ResetPasswordRequest::class;
    }
}
