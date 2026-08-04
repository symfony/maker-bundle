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

use Symfony\Bundle\MakerBundle\Maker\MakeWebhook;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeWebhook::class, profile: Profiles::MEGA)]
final class MakeWebhookTest extends MakerTestCase
{
    #[LegacyCase('MakeWebhookTest::it_makes_webhook_with_no_prior_config_file')]
    public function testItMakesWebhookWithNoPriorConfigFile()
    {
        $this->app->runMaker()
            ->answer('Name of the webhook to create', 'remote_service')
            ->acceptDefault('Add a RequestMatcher')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated(
                'src/Webhook/RemoteServiceRequestParser.php',
                'src/RemoteEvent/RemoteServiceWebhookConsumer.php',
            );

        $this->app->assertFileContains('src/Webhook/RemoteServiceRequestParser.php', 'use Symfony\Component\Webhook\Client\AbstractRequestParser;');
        $this->app->assertFileContains('src/RemoteEvent/RemoteServiceWebhookConsumer.php', "#[AsRemoteEventConsumer('remote_service')]");

        $webhookConfig = $this->app->readYaml('config/packages/webhook.yaml');
        self::assertSame('App\\Webhook\\RemoteServiceRequestParser', $webhookConfig['framework']['webhook']['routing']['remote_service']['service']);
        self::assertSame('your_secret_here', $webhookConfig['framework']['webhook']['routing']['remote_service']['secret']);

        $this->assertWebhookExecutes('RemoteService', 'remote_service');
    }

    #[LegacyCase('MakeWebhookTest::it_makes_webhook_with_prior_webhook')]
    public function testItMakesWebhookWithPriorWebhook()
    {
        $this->app->copyFixture('webhook.yaml', 'config/packages/webhook.yaml');
        $this->app->copyFixture('RemoteServiceRequestParser.php', 'src/Webhook/RemoteServiceRequestParser.php');
        $this->app->copyFixture('RemoteServiceWebhookConsumer.php', 'src/RemoteEvent/RemoteServiceWebhookConsumer.php');

        $this->app->runMaker()
            ->answer('Name of the webhook to create', 'another_remote_service')
            ->acceptDefault('Add a RequestMatcher')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated(
                'src/Webhook/AnotherRemoteServiceRequestParser.php',
                'src/RemoteEvent/AnotherRemoteServiceWebhookConsumer.php',
            );

        $webhookConfig = $this->app->readYaml('config/packages/webhook.yaml');

        // original config must not be modified
        self::assertSame('App\\Webhook\\RemoteServiceRequestParser', $webhookConfig['framework']['webhook']['routing']['remote_service']['service']);
        self::assertSame('%env(REMOTE_SERVICE_WEBHOOK_SECRET)%', $webhookConfig['framework']['webhook']['routing']['remote_service']['secret']);

        // new config must be added
        self::assertSame('App\\Webhook\\AnotherRemoteServiceRequestParser', $webhookConfig['framework']['webhook']['routing']['another_remote_service']['service']);
        self::assertSame('your_secret_here', $webhookConfig['framework']['webhook']['routing']['another_remote_service']['secret']);

        $this->assertWebhookExecutes('AnotherRemoteService', 'another_remote_service');
    }

    #[LegacyCase('MakeWebhookTest::it_makes_webhook_with_single_matcher')]
    public function testItMakesWebhookWithSingleMatcher()
    {
        $this->app->runMaker()
            ->answer('Name of the webhook to create', 'remote_service')
            ->answer('Add a RequestMatcher', '4')
            ->acceptDefault('Add another RequestMatcher')
            ->run()
            ->assertOutputContains('Success');

        $this->app->assertFileContains('src/Webhook/RemoteServiceRequestParser.php', 'use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;');
        $this->app->assertFileContains('src/Webhook/RemoteServiceRequestParser.php', 'return new IsJsonRequestMatcher();');

        $this->assertWebhookExecutes('RemoteService', 'remote_service');
    }

    #[LegacyCase('MakeWebhookTest::it_makes_webhook_with_multiple_matchers')]
    public function testItMakesWebhookWithMultipleMatchers()
    {
        $this->app->runMaker()
            ->answer('Name of the webhook to create', 'remote_service')
            ->answer('Add a RequestMatcher', '4')
            ->answer('Add another RequestMatcher', '6')
            ->acceptDefault('Add another RequestMatcher')
            ->run()
            ->assertOutputContains('Success');

        $parserSource = $this->app->readFile('src/Webhook/RemoteServiceRequestParser.php');
        self::assertStringContainsString('use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;', $parserSource);
        self::assertStringContainsString('use Symfony\Component\HttpFoundation\RequestMatcher\PortRequestMatcher;', $parserSource);
        self::assertStringContainsString('use Symfony\Component\HttpFoundation\ChainRequestMatcher;', $parserSource);
        self::assertStringContainsString(
            <<<EOF
                        return new ChainRequestMatcher([
                            new IsJsonRequestMatcher(),
                            new PortRequestMatcher(443),
                        ]);
                EOF,
            $parserSource,
        );

        $this->assertWebhookExecutes('RemoteService', 'remote_service');
    }

    #[LegacyCase('MakeWebhookTest::it_makes_webhook_with_expression_language_injection')]
    public function testItMakesWebhookWithExpressionLanguageInjection()
    {
        $this->app->runMaker()
            ->answer('Name of the webhook to create', 'remote_service')
            ->answer('Add a RequestMatcher', '4')
            ->answer('Add another RequestMatcher', '1')
            ->acceptDefault('Add another RequestMatcher')
            ->run()
            ->assertOutputContains('Success');

        $parserSource = $this->app->readFile('src/Webhook/RemoteServiceRequestParser.php');
        self::assertStringContainsString('use Symfony\Component\HttpFoundation\RequestMatcher\ExpressionRequestMatcher;', $parserSource);
        self::assertStringContainsString('use Symfony\Component\ExpressionLanguage\Expression;', $parserSource);
        self::assertStringContainsString('use Symfony\Component\ExpressionLanguage\ExpressionLanguage;', $parserSource);
        self::assertStringContainsString(
            <<<EOF
                        return new ChainRequestMatcher([
                            new IsJsonRequestMatcher(),
                            new ExpressionRequestMatcher(new ExpressionLanguage(), new Expression('expression')),
                        ]);
                EOF,
            $parserSource,
        );

        $this->assertWebhookExecutes('RemoteService', 'remote_service');
    }

    private function assertWebhookExecutes(string $classPrefix, string $webhookName): void
    {
        $this->app->assertGeneratedCodeRuns(<<<PHP
            \$parser = new \\App\\Webhook\\{$classPrefix}RequestParser();
            self::assertInstanceOf(
                \\Symfony\\Component\\HttpFoundation\\RequestMatcherInterface::class,
                (new \\ReflectionMethod(\$parser, 'getRequestMatcher'))->invoke(\$parser),
            );

            \$consumer = new \\App\\RemoteEvent\\{$classPrefix}WebhookConsumer();
            \$attributes = (new \\ReflectionClass(\$consumer))
                ->getAttributes(\\Symfony\\Component\\RemoteEvent\\Attribute\\AsRemoteEventConsumer::class);
            self::assertCount(1, \$attributes);
            self::assertSame('{$webhookName}', \$attributes[0]->newInstance()->name);
            PHP,
            covers: [
                "src/Webhook/{$classPrefix}RequestParser.php",
                "src/RemoteEvent/{$classPrefix}WebhookConsumer.php",
            ],
        );
    }
}
