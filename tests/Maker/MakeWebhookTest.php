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
                    'remote_service',    // webhook name
                    '',                  // skip adding matchers
                ]);

                $this->assertStringContainsString('Success', $output);

                $outputExpectations = [
                    'src/Webhook/RemoteServiceRequestParser.php' => 'use Symfony\Component\Webhook\Client\AbstractRequestParser;',
                    'src/RemoteEvent/RemoteServiceWebhookConsumer.php' => '#[AsRemoteEventConsumer(\'remote_service\')]',
                ];

                $this->assertStringContainsString('created: ', $output);

                foreach ($outputExpectations as $expectedFileName => $expectedContent) {
                    $path = $runner->getPath($expectedFileName);

                    $this->assertStringContainsString($expectedFileName, $output);
                    $this->assertFileExists($runner->getPath($expectedFileName));
                    $this->assertStringContainsString($expectedContent, file_get_contents($path));
                }

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
            ->addExtraDependencies('symfony/webhook')
            ->run(function (MakerTestRunner $runner) {
                $runner->copy('make-webhook/webhook.yaml', 'config/packages/webhook.yaml');
                $runner->copy('make-webhook/RemoteServiceRequestParser.php', 'src/Webhook/RemoteServiceRequestParser.php');
                $runner->copy('make-webhook/RemoteServiceWebhookConsumer.php', 'src/RemoteEvent/RemoteServiceWebhookConsumer.php');

                $output = $runner->runMaker([
                    'another_remote_service',    // webhook name
                    '',                          // skip adding matchers
                ]);

                $this->assertStringContainsString('Success', $output);

                $outputExpectations = [
                    'src/Webhook/AnotherRemoteServiceRequestParser.php' => 'use Symfony\Component\Webhook\Client\AbstractRequestParser;',
                    'src/RemoteEvent/AnotherRemoteServiceWebhookConsumer.php' => '#[AsRemoteEventConsumer(\'another_remote_service\')]',
                ];

                $this->assertStringContainsString('created: ', $output);

                foreach ($outputExpectations as $expectedFileName => $expectedContent) {
                    $path = $runner->getPath($expectedFileName);

                    $this->assertStringContainsString($expectedFileName, $output);
                    $this->assertFileExists($runner->getPath($expectedFileName));
                    $this->assertStringContainsString($expectedContent, file_get_contents($path));
                }

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
                    'remote_service',  // webhook name
                    '4',               // 'IsJsonRequestMatcher',
                ]);

                $this->assertStringContainsString('Success', $output);

                $outputExpectations = [
                    $parserFileName = 'src/Webhook/RemoteServiceRequestParser.php' => 'use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;',
                    'src/RemoteEvent/RemoteServiceWebhookConsumer.php' => '#[AsRemoteEventConsumer(\'remote_service\')]',
                ];

                $this->assertStringContainsString('created: ', $output);

                foreach ($outputExpectations as $expectedFileName => $expectedContent) {
                    $path = $runner->getPath($expectedFileName);

                    $this->assertStringContainsString($expectedFileName, $output);
                    $this->assertFileExists($runner->getPath($expectedFileName));
                    $this->assertStringContainsString($expectedContent, file_get_contents($path));
                }

                $this->assertStringContainsString(
                    'return new IsJsonRequestMatcher();',
                    file_get_contents($runner->getPath($parserFileName))
                );
            }),
        ];

        yield 'it_makes_webhook_with_multiple_matchers' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    'remote_service',  // webhook name
                    '4',               // 'IsJsonRequestMatcher',
                    '6',               // 'PortRequestMatcher',
                ]);

                $this->assertStringContainsString('Success', $output);

                $outputExpectations = [
                    $parserFileName = 'src/Webhook/RemoteServiceRequestParser.php' => 'use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;',
                    'src/RemoteEvent/RemoteServiceWebhookConsumer.php' => '#[AsRemoteEventConsumer(\'remote_service\')]',
                ];

                $this->assertStringContainsString('created: ', $output);

                foreach ($outputExpectations as $expectedFileName => $expectedContent) {
                    $path = $runner->getPath($expectedFileName);

                    $this->assertStringContainsString($expectedFileName, $output);
                    $this->assertFileExists($runner->getPath($expectedFileName));
                    $this->assertStringContainsString($expectedContent, file_get_contents($path));
                }

                $requestParserSource = file_get_contents($runner->getPath($parserFileName));

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
                    'remote_service',  // webhook name
                    '4',               // 'IsJsonRequestMatcher',
                    '1',               // 'ExpressionRequestMatcher',
                ]);

                $this->assertStringContainsString('Success', $output);

                $outputExpectations = [
                    $parserFileName = 'src/Webhook/RemoteServiceRequestParser.php' => 'use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;',
                    'src/RemoteEvent/RemoteServiceWebhookConsumer.php' => '#[AsRemoteEventConsumer(\'remote_service\')]',
                ];

                $this->assertStringContainsString('created: ', $output);

                foreach ($outputExpectations as $expectedFileName => $expectedContent) {
                    $path = $runner->getPath($expectedFileName);

                    $this->assertStringContainsString($expectedFileName, $output);
                    $this->assertFileExists($runner->getPath($expectedFileName));
                    $this->assertStringContainsString($expectedContent, file_get_contents($path));
                }

                $requestParserSource = file_get_contents($runner->getPath($parserFileName));

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
