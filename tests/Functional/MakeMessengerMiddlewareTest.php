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

use Symfony\Bundle\MakerBundle\Maker\MakeMessengerMiddleware;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeMessengerMiddleware::class, profile: Profiles::MEGA)]
final class MakeMessengerMiddlewareTest extends MakerTestCase
{
    #[LegacyCase('MakeMessengerMiddlewareTest::it_generates_messenger_middleware')]
    public function testItGeneratesMessengerMiddleware()
    {
        $this->app->runMaker()
            ->answer('The name of the middleware class', 'CustomMiddleware')
            ->run()
            ->assertCreated('src/Middleware/CustomMiddleware.php');

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $middleware = new \App\Middleware\CustomMiddleware();
            self::assertInstanceOf(\Symfony\Component\Messenger\Middleware\MiddlewareInterface::class, $middleware);

            // run the middleware for real inside a bus
            $bus = new \Symfony\Component\Messenger\MessageBus([$middleware]);
            $envelope = $bus->dispatch(new \stdClass());
            self::assertInstanceOf(\Symfony\Component\Messenger\Envelope::class, $envelope);
            PHP,
            covers: ['src/Middleware/CustomMiddleware.php'],
        );
    }
}
