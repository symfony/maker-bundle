<?php

namespace App\Tests\Functional\User;

use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ProfileTest extends KernelTestCase
{
    use Factories;
    use HasBrowser;
    use ResetDatabase;

    public function testCanUpdateProfile(): void
    {
        $user = UserFactory::createOne(['name' => 'Mary Edwards']);

        $this->assertSame('Mary Edwards', $user->getName());

        $this->browser()
            ->actingAs($user->object())
            ->visit('/user')
            ->assertFieldEquals('Name', 'Mary Edwards')
            ->fillField('Name', 'John Smith')
            ->click('Save')
            ->assertOn('/')
            ->assertSuccessful()
            ->assertSeeIn('.alert', 'You\'ve successfully updated your profile.')
        ;

        $this->assertSame('John Smith', $user->getName());
    }

    public function testNameIsRequired(): void
    {
        $user = UserFactory::createOne(['name' => 'Mary Edwards']);

        UserFactory::assert()->exists(['name' => 'Mary Edwards']);

        $this->browser()
            ->actingAs($user->object())
            ->visit('/user')
            ->fillField('Name', '')
            ->click('Save')
            ->assertOn('/user')
            ->assertSuccessful()
            ->assertSee('Name is required')
        ;

        UserFactory::assert()->exists(['name' => 'Mary Edwards']);
    }

    public function testCannotAccessProfilePageIfNotLoggedIn(): void
    {
        $this->browser()
            ->visit('/user')
            ->assertOn('/login')
        ;
    }
}
