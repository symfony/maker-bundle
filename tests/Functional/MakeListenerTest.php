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

use Symfony\Bundle\MakerBundle\Maker\MakeListener;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeListener::class, profile: Profiles::MEGA)]
final class MakeListenerTest extends MakerTestCase
{
    private const DISPATCH_REQUEST_EVENT = <<<'PHP'
        $dispatcher->dispatch(new \Symfony\Component\HttpKernel\Event\RequestEvent(
            self::$kernel,
            \Symfony\Component\HttpFoundation\Request::create('/'),
            \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
        ));
        PHP;

    #[LegacyCase('MakeListenerTest::it_make_subscriber_without_conventional_name')]
    public function testItMakeSubscriberWithoutConventionalName()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your event listener or subscriber', 'foo')
            ->answer('Do you want to generate an event listener or subscriber?', 'Subscriber')
            ->answer('What event do you want to listen to', 'kernel.request')
            ->run()
            ->assertCreated('src/EventSubscriber/FooSubscriber.php');

        $this->app->assertFileMatchesFixture('src/EventSubscriber/FooSubscriber.php', 'expected/EventSubscriber/FooSubscriber.php');
        $this->assertSubscriberExecutes('FooSubscriber', '\Symfony\Component\HttpKernel\Event\RequestEvent::class', 'onRequestEvent', self::DISPATCH_REQUEST_EVENT);
    }

    #[LegacyCase('MakeListenerTest::it_make_listener_without_conventional_name')]
    public function testItMakeListenerWithoutConventionalName()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your event listener or subscriber', 'foo')
            ->answer('Do you want to generate an event listener or subscriber?', 'Listener')
            ->answer('What event do you want to listen to', 'kernel.request')
            ->run()
            ->assertCreated('src/EventListener/FooListener.php');

        $this->app->assertFileMatchesFixture('src/EventListener/FooListener.php', 'expected/EventListener/FooListener.php');
        $this->assertListenerExecutes('FooListener', 'onRequestEvent', '$listener->onRequestEvent($event = new \Symfony\Component\HttpKernel\Event\RequestEvent(self::$kernel, \Symfony\Component\HttpFoundation\Request::create(\'/\'), \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST));');
    }

    #[LegacyCase('MakeListenerTest::it_makes_subscriber_for_known_event')]
    public function testItMakesSubscriberForKnownEvent()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your event listener or subscriber', 'FooBarSubscriber')
            ->answer('What event do you want to listen to', 'kernel.request')
            ->run()
            ->assertCreated('src/EventSubscriber/FooBarSubscriber.php');

        $this->app->assertFileMatchesFixture('src/EventSubscriber/FooBarSubscriber.php', 'expected/EventSubscriber/FooBarSubscriber.php');
        $this->assertSubscriberExecutes('FooBarSubscriber', '\Symfony\Component\HttpKernel\Event\RequestEvent::class', 'onRequestEvent', self::DISPATCH_REQUEST_EVENT);
    }

    #[LegacyCase('MakeListenerTest::it_makes_subscriber_for_custom_event_class')]
    public function testItMakesSubscriberForCustomEventClass()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your event listener or subscriber', 'CustomSubscriber')
            ->answer('What event do you want to listen to', \Symfony\Bundle\MakerBundle\Generator::class)
            ->run()
            ->assertCreated('src/EventSubscriber/CustomSubscriber.php');

        $this->app->assertFileMatchesFixture('src/EventSubscriber/CustomSubscriber.php', 'expected/EventSubscriber/CustomSubscriber.php');
        // dispatching an instance routes through the real dispatcher to the handler
        $this->assertSubscriberExecutes('CustomSubscriber', '\Symfony\Bundle\MakerBundle\Generator::class', 'onGenerator', '$dispatcher->dispatch((new \ReflectionClass(\Symfony\Bundle\MakerBundle\Generator::class))->newInstanceWithoutConstructor());');
    }

    #[LegacyCase('MakeListenerTest::it_makes_subscriber_for_unknown_event_class')]
    public function testItMakesSubscriberForUnknownEventClass()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your event listener or subscriber', 'UnknownSubscriber')
            ->answer('What event do you want to listen to', 'foo.unknown_event')
            ->run()
            ->assertCreated('src/EventSubscriber/UnknownSubscriber.php');

        $this->app->assertFileMatchesFixture('src/EventSubscriber/UnknownSubscriber.php', 'expected/EventSubscriber/UnknownSubscriber.php');
        $this->assertSubscriberExecutes('UnknownSubscriber', "'foo.unknown_event'", 'onFooUnknownEvent', "\$dispatcher->dispatch(new \Symfony\Contracts\EventDispatcher\Event(), 'foo.unknown_event');");
    }

    #[LegacyCase('MakeListenerTest::it_makes_listener_for_known_event')]
    public function testItMakesListenerForKnownEvent()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your event listener or subscriber', 'FooBarListener')
            ->answer('What event do you want to listen to', 'kernel.request')
            ->run()
            ->assertCreated('src/EventListener/FooBarListener.php');

        $this->app->assertFileMatchesFixture('src/EventListener/FooBarListener.php', 'expected/EventListener/FooBarListener.php');
        $this->assertListenerExecutes('FooBarListener', 'onRequestEvent', '$listener->onRequestEvent(new \Symfony\Component\HttpKernel\Event\RequestEvent(self::$kernel, \Symfony\Component\HttpFoundation\Request::create(\'/\'), \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST));');
    }

    #[LegacyCase('MakeListenerTest::it_makes_listener_for_custom_event_class')]
    public function testItMakesListenerForCustomEventClass()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your event listener or subscriber', 'CustomListener')
            ->answer('What event do you want to listen to', \Symfony\Bundle\MakerBundle\Generator::class)
            ->run()
            ->assertCreated('src/EventListener/CustomListener.php');

        $this->app->assertFileMatchesFixture('src/EventListener/CustomListener.php', 'expected/EventListener/CustomListener.php');
        $this->assertListenerExecutes('CustomListener', 'onGenerator', '$listener->onGenerator((new \ReflectionClass(\Symfony\Bundle\MakerBundle\Generator::class))->newInstanceWithoutConstructor());');
    }

    #[LegacyCase('MakeListenerTest::it_makes_listener_for_unknown_event_class')]
    public function testItMakesListenerForUnknownEventClass()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your event listener or subscriber', 'UnknownListener')
            ->answer('What event do you want to listen to', 'foo.unknown_event')
            ->run()
            ->assertCreated('src/EventListener/UnknownListener.php');

        $this->app->assertFileMatchesFixture('src/EventListener/UnknownListener.php', 'expected/EventListener/UnknownListener.php');
        $this->assertListenerExecutes('UnknownListener', 'onFooUnknownEvent', '$listener->onFooUnknownEvent(new \Symfony\Contracts\EventDispatcher\Event());');
    }

    #[LegacyCase('MakeListenerTest::it_makes_listener_for_known_event_by_id')]
    public function testItMakesListenerForKnownEventById()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your event listener or subscriber', 'FooListener')
            ->answer('What event do you want to listen to', 'kernel.request')
            ->answerIfAsked('Did you mean', 'y')
            ->run()
            ->assertCreated('src/EventListener/FooListener.php');

        $this->app->assertFileMatchesFixture('src/EventListener/FooListener.php', 'expected/EventListener/FooListener.php');
        $this->assertListenerExecutes('FooListener', 'onRequestEvent', '$listener->onRequestEvent(new \Symfony\Component\HttpKernel\Event\RequestEvent(self::$kernel, \Symfony\Component\HttpFoundation\Request::create(\'/\'), \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST));');
    }

    #[LegacyCase('MakeListenerTest::it_makes_listener_for_known_event_by_short_class_name')]
    public function testItMakesListenerForKnownEventByShortClassName()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your event listener or subscriber', 'BarListener')
            ->answer('What event do you want to listen to', 'RequestEvent')
            ->answer('Did you mean', 'y')
            ->run()
            ->assertCreated('src/EventListener/BarListener.php');

        $this->app->assertFileMatchesFixture('src/EventListener/BarListener.php', 'expected/EventListener/BarListener.php');
        $this->assertListenerExecutes('BarListener', 'onRequestEvent', '$listener->onRequestEvent(new \Symfony\Component\HttpKernel\Event\RequestEvent(self::$kernel, \Symfony\Component\HttpFoundation\Request::create(\'/\'), \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST));');
    }

    #[LegacyCase('MakeListenerTest::it_makes_listener_for_known_event_by_id_with_2_letters_typo')]
    public function testItMakesListenerForKnownEventByIdWithTwoLettersTypo()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your event listener or subscriber', 'FooListener')
            ->answer('What event do you want to listen to', 'kernem.reques')
            ->answer('Did you mean', 'y')
            ->run()
            ->assertCreated('src/EventListener/FooListener.php');

        $this->app->assertFileMatchesFixture('src/EventListener/FooListener.php', 'expected/EventListener/FooListener.php');
        $this->assertListenerExecutes('FooListener', 'onRequestEvent', '$listener->onRequestEvent(new \Symfony\Component\HttpKernel\Event\RequestEvent(self::$kernel, \Symfony\Component\HttpFoundation\Request::create(\'/\'), \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST));');
    }

    #[LegacyCase('MakeListenerTest::it_makes_listener_for_known_event_by_short_class_name_with_2_letters_typo')]
    public function testItMakesListenerForKnownEventByShortClassNameWithTwoLettersTypo()
    {
        $this->app->runMaker()
            ->answer('Choose a class name for your event listener or subscriber', 'BarListener')
            ->answer('What event do you want to listen to', 'RequstEveny')
            ->answer('Did you mean', 'y')
            ->run()
            ->assertCreated('src/EventListener/BarListener.php');

        $this->app->assertFileMatchesFixture('src/EventListener/BarListener.php', 'expected/EventListener/BarListener.php');
        $this->assertListenerExecutes('BarListener', 'onRequestEvent', '$listener->onRequestEvent(new \Symfony\Component\HttpKernel\Event\RequestEvent(self::$kernel, \Symfony\Component\HttpFoundation\Request::create(\'/\'), \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST));');
    }

    private function assertSubscriberExecutes(string $shortClass, string $eventExpression, string $method, string $dispatchCode = ''): void
    {
        $this->app->assertGeneratedCodeRuns(<<<PHP
            \$subscriber = new \\App\\EventSubscriber\\{$shortClass}();
            self::assertSame([{$eventExpression} => '{$method}'], \\App\\EventSubscriber\\{$shortClass}::getSubscribedEvents());

            \$dispatcher = new \\Symfony\\Component\\EventDispatcher\\EventDispatcher();
            \$dispatcher->addSubscriber(\$subscriber);
            {$dispatchCode}
            PHP,
            covers: ["src/EventSubscriber/{$shortClass}.php"],
        );
    }

    private function assertListenerExecutes(string $shortClass, string $method, string $invokeCode = ''): void
    {
        $this->app->assertGeneratedCodeRuns(<<<PHP
            \$listener = new \\App\\EventListener\\{$shortClass}();

            \$attributes = (new \\ReflectionMethod(\$listener, '{$method}'))
                ->getAttributes(\\Symfony\\Component\\EventDispatcher\\Attribute\\AsEventListener::class);
            self::assertCount(1, \$attributes);
            \$attributes[0]->newInstance();
            {$invokeCode}
            PHP,
            covers: ["src/EventListener/{$shortClass}.php"],
        );
    }
}
