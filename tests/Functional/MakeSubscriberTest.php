<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\MakerBundle\Maker\MakeSubscriber;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

/**
 * @group legacy
 */
#[Group('legacy')]
#[MakerTest(maker: MakeSubscriber::class, profile: Profiles::MEGA)]
final class MakeSubscriberTest extends MakerTestCase
{
    #[LegacyCase('MakeSubscriberTest::it_makes_subscriber_for_known_event')]
    public function testItMakesSubscriberForKnownEvent()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your event listener or subscriber', 'FooBar')
            ->answer('What event do you want to listen to', 'kernel.request')
            ->run()
            ->assertCreated('src/EventSubscriber/FooBarSubscriber.php');

        $this->app->assertFileContains('src/EventSubscriber/FooBarSubscriber.php', "RequestEvent::class => 'onRequestEvent'");

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $subscriber = new \App\EventSubscriber\FooBarSubscriber();
            self::assertSame(
                [\Symfony\Component\HttpKernel\Event\RequestEvent::class => 'onRequestEvent'],
                \App\EventSubscriber\FooBarSubscriber::getSubscribedEvents(),
            );

            $dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
            $dispatcher->addSubscriber($subscriber);
            $dispatcher->dispatch(new \Symfony\Component\HttpKernel\Event\RequestEvent(
                self::$kernel,
                \Symfony\Component\HttpFoundation\Request::create('/'),
                \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
            ));
            PHP,
            covers: ['src/EventSubscriber/FooBarSubscriber.php'],
        );
    }

    #[LegacyCase('MakeSubscriberTest::it_makes_subscriber_for_custom_event_class')]
    public function testItMakesSubscriberForCustomEventClass()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your event listener or subscriber', 'FooBar')
            ->answer('What event do you want to listen to', \Symfony\Bundle\MakerBundle\Generator::class)
            ->run()
            ->assertCreated('src/EventSubscriber/FooBarSubscriber.php');

        $this->app->assertFileContains('src/EventSubscriber/FooBarSubscriber.php', "Generator::class => 'onGenerator'");

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $subscriber = new \App\EventSubscriber\FooBarSubscriber();
            self::assertSame(
                [\Symfony\Bundle\MakerBundle\Generator::class => 'onGenerator'],
                \App\EventSubscriber\FooBarSubscriber::getSubscribedEvents(),
            );

            // registering validates the callable mapping for real
            (new \Symfony\Component\EventDispatcher\EventDispatcher())->addSubscriber($subscriber);
            PHP,
            covers: ['src/EventSubscriber/FooBarSubscriber.php'],
        );
    }

    #[LegacyCase('MakeSubscriberTest::it_makes_subscriber_for_unknown_event_class')]
    public function testItMakesSubscriberForUnknownEvent()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your event listener or subscriber', 'FooBar')
            ->answer('What event do you want to listen to', 'foo.unknown_event')
            ->run()
            ->assertCreated('src/EventSubscriber/FooBarSubscriber.php');

        $this->app->assertFileContains('src/EventSubscriber/FooBarSubscriber.php', "'foo.unknown_event' => 'onFooUnknownEvent'");

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $subscriber = new \App\EventSubscriber\FooBarSubscriber();
            self::assertSame(
                ['foo.unknown_event' => 'onFooUnknownEvent'],
                \App\EventSubscriber\FooBarSubscriber::getSubscribedEvents(),
            );

            $dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
            $dispatcher->addSubscriber($subscriber);
            $dispatcher->dispatch(new \Symfony\Contracts\EventDispatcher\Event(), 'foo.unknown_event');
            PHP,
            covers: ['src/EventSubscriber/FooBarSubscriber.php'],
        );
    }
}
