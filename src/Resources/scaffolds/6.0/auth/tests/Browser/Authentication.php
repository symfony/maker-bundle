<?php

namespace App\Tests\Browser;

use PHPUnit\Framework\Assert;
use Symfony\Bundle\SecurityBundle\DataCollector\SecurityDataCollector;
use Symfony\Component\BrowserKit\CookieJar;
use Zenstruck\Browser\Component;
use Zenstruck\Browser\KernelBrowser;

class Authentication extends Component
{
    public static function assertAuthenticated(): \Closure
    {
        return static function(self $auth) {
            Assert::assertTrue($auth->collector()->isAuthenticated());
        };
    }

    public static function assertAuthenticatedAs(string $email): \Closure
    {
        return static function(self $auth) use ($email) {
            $collector = $auth->collector();

            Assert::assertTrue($collector->isAuthenticated());
            Assert::assertSame($email, $collector->getUser());
        };
    }

    public static function assertNotAuthenticated(): \Closure
    {
        return static function(self $auth) {
            Assert::assertFalse($auth->collector()->isAuthenticated());
        };
    }

    public static function expireSession(): \Closure
    {
        return static function(CookieJar $cookies) {
            $cookies->expire('MOCKSESSID');
        };
    }

    private function collector(): SecurityDataCollector
    {
        $browser = $this->browser();

        assert($browser instanceof KernelBrowser);

        $collector = $browser
            ->withProfiling()
            ->visit('/')
            ->profile()
            ->getCollector('security')
        ;

        assert($collector instanceof SecurityDataCollector);

        return $collector;
    }
}
