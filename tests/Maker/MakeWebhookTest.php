<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeWebhook;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

class MakeWebhookTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeWebhook::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_makes_webhook_with_no_prior_config_file' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // webhook name
                    'remote_service',
                    // skip adding matchers
                    '',
                ]);
                $this->assertStringContainsString('created:', $output);
                $this->assertFileExists($runner->getPath('src/Webhook/RemoteServiceRequestParser.php'));
                $this->assertStringContainsString(
                    'use Symfony\Component\Webhook\Client\AbstractRequestParser;',
                    file_get_contents($runner->getPath('src/Webhook/RemoteServiceRequestParser.php'))
                );
                $this->assertFileExists($runner->getPath('src/RemoteEvent/RemoteServiceWebhookHandler.php'));
                $this->assertStringContainsString(
                    '#[AsRemoteEventConsumer(\'remote_service\')]',
                    file_get_contents($runner->getPath('src/RemoteEvent/RemoteServiceWebhookHandler.php')),
                );
                $securityConfig = $runner->readYaml('config/packages/webhook.yaml');
                $this->assertEquals(
                    'App\\Webhook\\RemoteServiceRequestParser',
                    $securityConfig['framework']['webhook']['routing']['remote_service']['service']
                );
                $this->assertEquals(
                    'your_secret_here',
                    $securityConfig['framework']['webhook']['routing']['remote_service']['secret']
                );
            }),
        ];

        yield 'it_makes_webhook_with_prior_webhook' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->copy('make-webhook/webhook.yaml', 'config/packages/webhook.yaml');
                $runner->copy('make-webhook/RemoteServiceRequestParser.php', 'src/Webhook/RemoteServiceRequestParser.php');
                $runner->copy('make-webhook/RemoteServiceWebhookHandler.php', 'src/RemoteEvent/RemoteServiceWebhookHandler.php');
                $output = $runner->runMaker([
                    // webhook name
                    'another_remote_service',
                    // skip adding matchers
                    '',
                ]);
                $this->assertStringContainsString('created:', $output);
                $this->assertFileExists($runner->getPath('src/Webhook/AnotherRemoteServiceRequestParser.php'));
                $this->assertFileExists($runner->getPath('src/RemoteEvent/AnotherRemoteServiceWebhookHandler.php'));
                $securityConfig = $runner->readYaml('config/packages/webhook.yaml');
                // original config should not be modified
                $this->assertArrayHasKey('remote_service', $securityConfig['framework']['webhook']['routing']);
                $this->assertEquals(
                    'App\\Webhook\\RemoteServiceRequestParser',
                    $securityConfig['framework']['webhook']['routing']['remote_service']['service']
                );
                $this->assertEquals(
                    '%env(REMOTE_SERVICE_WEBHOOK_SECRET)%',
                    $securityConfig['framework']['webhook']['routing']['remote_service']['secret']
                );
                // new config should be added
                $this->assertArrayHasKey('another_remote_service', $securityConfig['framework']['webhook']['routing']);
                $this->assertEquals(
                    'App\\Webhook\\AnotherRemoteServiceRequestParser',
                    $securityConfig['framework']['webhook']['routing']['another_remote_service']['service']
                );
                $this->assertEquals(
                    'your_secret_here',
                    $securityConfig['framework']['webhook']['routing']['another_remote_service']['secret']
                );
            }),
        ];

        yield 'it_makes_webhook_with_single_matcher' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // webhook name
                    'remote_service',
                    // add a matcher
                    'Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher',
                ]);
                $this->assertStringContainsString('created:', $output);
                $this->assertFileExists($runner->getPath('src/Webhook/RemoteServiceRequestParser.php'));
                $this->assertFileExists($runner->getPath('src/RemoteEvent/RemoteServiceWebhookHandler.php'));
                $requestParserSource = file_get_contents($runner->getPath('src/Webhook/RemoteServiceRequestParser.php'));
                $this->assertStringContainsString(
                    'use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;',
                    $requestParserSource
                );
                $this->assertStringContainsString(
                    'return new IsJsonRequestMatcher();',
                    $requestParserSource
                );
            }),
        ];

        yield 'it_makes_webhook_with_multiple_matchers' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // webhook name
                    'remote_service',
                    // add matchers
                    'Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher',
                    'Symfony\Component\HttpFoundation\RequestMatcher\PortRequestMatcher',
                ]);
                $this->assertStringContainsString('created:', $output);
                $this->assertFileExists($runner->getPath('src/Webhook/RemoteServiceRequestParser.php'));
                $this->assertFileExists($runner->getPath('src/RemoteEvent/RemoteServiceWebhookHandler.php'));
                $requestParserSource = file_get_contents($runner->getPath('src/Webhook/RemoteServiceRequestParser.php'));
                $this->assertStringContainsString(
                    'use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;',
                    $requestParserSource
                );
                $this->assertStringContainsString(
                    'use Symfony\Component\HttpFoundation\RequestMatcher\PortRequestMatcher;',
                    $requestParserSource
                );
                $this->assertStringContainsString(
                    'use Symfony\Component\HttpFoundation\ChainRequestMatcher;',
                    $requestParserSource
                );
                $this->assertStringContainsString(
                    <<<EOF
                                return new ChainRequestMatcher([
                                    new IsJsonRequestMatcher(),
                                    new PortRequestMatcher(443),
                                ]);
                        EOF,
                    $requestParserSource
                );
            }),
        ];

        yield 'it_makes_webhook_with_expression_language_injection' => [$this->createMakerTest()
            ->addExtraDependencies('symfony/expression-language')
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // webhook name
                    'remote_service',
                    // add matchers
                    'Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher',
                    'Symfony\Component\HttpFoundation\RequestMatcher\ExpressionRequestMatcher',
                ]);
                $this->assertStringContainsString('created:', $output);
                $this->assertFileExists($runner->getPath('src/Webhook/RemoteServiceRequestParser.php'));
                $this->assertFileExists($runner->getPath('src/RemoteEvent/RemoteServiceWebhookHandler.php'));
                $requestParserSource = file_get_contents($runner->getPath('src/Webhook/RemoteServiceRequestParser.php'));
                $this->assertStringContainsString(
                    'use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;',
                    $requestParserSource
                );
                $this->assertStringContainsString(
                    'use Symfony\Component\HttpFoundation\RequestMatcher\ExpressionRequestMatcher;',
                    $requestParserSource
                );
                $this->assertStringContainsString(
                    'use Symfony\Component\HttpFoundation\ChainRequestMatcher;',
                    $requestParserSource
                );
                $this->assertStringContainsString(
                    'use Symfony\Component\ExpressionLanguage\Expression;',
                    $requestParserSource
                );
                $this->assertStringContainsString(
                    'use Symfony\Component\ExpressionLanguage\ExpressionLanguage;',
                    $requestParserSource
                );
                $this->assertStringContainsString(
                    <<<EOF
                                return new ChainRequestMatcher([
                                    new IsJsonRequestMatcher(),
                                    new ExpressionRequestMatcher(new ExpressionLanguage(), new Expression('expression')),
                                ]);
                        EOF,
                    $requestParserSource
                );
            }),
        ];
    }
}
