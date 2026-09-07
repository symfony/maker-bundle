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
use Symfony\Component\Yaml\Yaml;

class MakeWebhookTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeWebhook::class;
    }

    public static function getTestDetails(): \Generator
    {
        yield 'it_makes_webhook_with_no_prior_config_file' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    'remote_service',    // webhook name
                    '',                  // skip adding matchers
                ]);

                self::assertStringContainsString('Success', $output);

                $outputExpectations = [
                    'src/Webhook/RemoteServiceRequestParser.php' => 'use Symfony\Component\Webhook\Client\AbstractRequestParser;',
                    'src/RemoteEvent/RemoteServiceWebhookConsumer.php' => '#[AsRemoteEventConsumer(\'remote_service\')]',
                ];

                self::assertStringContainsString('created: ', $output);

                foreach ($outputExpectations as $expectedFileName => $expectedContent) {
                    $path = $runner->getPath($expectedFileName);

                    self::assertStringContainsString($expectedFileName, $output);
                    self::assertFileExists($runner->getPath($expectedFileName));
                    self::assertStringContainsString($expectedContent, file_get_contents($path));
                }

                $securityConfig = $runner->readYaml('config/packages/webhook.yaml');

                self::assertEquals(
                    'App\\Webhook\\RemoteServiceRequestParser',
                    $securityConfig['framework']['webhook']['routing']['remote_service']['service']
                );

                self::assertEquals(
                    'your_secret_here',
                    $securityConfig['framework']['webhook']['routing']['remote_service']['secret']
                );
            }),
        ];

        yield 'it_makes_webhook_non_interactively' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], 'remote_service --no-interaction');

                self::assertStringContainsString('Success', $output);
                self::assertFileExists($runner->getPath('src/Webhook/RemoteServiceRequestParser.php'));
                self::assertFileExists($runner->getPath('src/RemoteEvent/RemoteServiceWebhookConsumer.php'));

                $webhookConfig = $runner->readYaml('config/packages/webhook.yaml');

                self::assertEquals(
                    'App\\Webhook\\RemoteServiceRequestParser',
                    $webhookConfig['framework']['webhook']['routing']['remote_service']['service']
                );
            }),
        ];

        yield 'it_rejects_an_invalid_webhook_name_non_interactively' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], '"not a name" --no-interaction', allowedToFail: true);

                self::assertStringContainsString('can only have alphanumeric characters', $output);
                self::assertFileDoesNotExist($runner->getPath('config/packages/webhook.yaml'));
            }),
        ];

        yield 'it_makes_webhook_with_prior_webhook' => [self::buildMakerTest()
            ->addExtraDependencies('symfony/webhook')
            ->run(static function (MakerTestRunner $runner) {
                $runner->copy('make-webhook/webhook.yaml', 'config/packages/webhook.yaml');
                $runner->copy('make-webhook/RemoteServiceRequestParser.php', 'src/Webhook/RemoteServiceRequestParser.php');
                $runner->copy('make-webhook/RemoteServiceWebhookConsumer.php', 'src/RemoteEvent/RemoteServiceWebhookConsumer.php');

                $output = $runner->runMaker([
                    'another_remote_service',    // webhook name
                    '',                          // skip adding matchers
                ]);

                self::assertStringContainsString('Success', $output);

                $outputExpectations = [
                    'src/Webhook/AnotherRemoteServiceRequestParser.php' => 'use Symfony\Component\Webhook\Client\AbstractRequestParser;',
                    'src/RemoteEvent/AnotherRemoteServiceWebhookConsumer.php' => '#[AsRemoteEventConsumer(\'another_remote_service\')]',
                ];

                self::assertStringContainsString('created: ', $output);

                foreach ($outputExpectations as $expectedFileName => $expectedContent) {
                    $path = $runner->getPath($expectedFileName);

                    self::assertStringContainsString($expectedFileName, $output);
                    self::assertFileExists($runner->getPath($expectedFileName));
                    self::assertStringContainsString($expectedContent, file_get_contents($path));
                }

                $securityConfig = $runner->readYaml('config/packages/webhook.yaml');

                // original config should not be modified
                self::assertArrayHasKey('remote_service', $securityConfig['framework']['webhook']['routing']);

                self::assertEquals(
                    'App\\Webhook\\RemoteServiceRequestParser',
                    $securityConfig['framework']['webhook']['routing']['remote_service']['service']
                );

                self::assertEquals(
                    '%env(REMOTE_SERVICE_WEBHOOK_SECRET)%',
                    $securityConfig['framework']['webhook']['routing']['remote_service']['secret']
                );

                // new config should be added
                self::assertArrayHasKey('another_remote_service', $securityConfig['framework']['webhook']['routing']);

                self::assertEquals(
                    'App\\Webhook\\AnotherRemoteServiceRequestParser',
                    $securityConfig['framework']['webhook']['routing']['another_remote_service']['service']
                );

                self::assertEquals(
                    'your_secret_here',
                    $securityConfig['framework']['webhook']['routing']['another_remote_service']['secret']
                );
            }),
        ];

        yield 'it_makes_webhook_with_single_matcher' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    'remote_service',  // webhook name
                    '4',               // 'IsJsonRequestMatcher',
                ]);

                self::assertStringContainsString('Success', $output);

                $outputExpectations = [
                    $parserFileName = 'src/Webhook/RemoteServiceRequestParser.php' => 'use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;',
                    'src/RemoteEvent/RemoteServiceWebhookConsumer.php' => '#[AsRemoteEventConsumer(\'remote_service\')]',
                ];

                self::assertStringContainsString('created: ', $output);

                foreach ($outputExpectations as $expectedFileName => $expectedContent) {
                    $path = $runner->getPath($expectedFileName);

                    self::assertStringContainsString($expectedFileName, $output);
                    self::assertFileExists($runner->getPath($expectedFileName));
                    self::assertStringContainsString($expectedContent, file_get_contents($path));
                }

                self::assertStringContainsString(
                    'return new IsJsonRequestMatcher();',
                    file_get_contents($runner->getPath($parserFileName))
                );
            }),
        ];

        yield 'it_makes_webhook_with_multiple_matchers' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    'remote_service',  // webhook name
                    '4',               // 'IsJsonRequestMatcher',
                    '6',               // 'PortRequestMatcher',
                ]);

                self::assertStringContainsString('Success', $output);

                $outputExpectations = [
                    $parserFileName = 'src/Webhook/RemoteServiceRequestParser.php' => 'use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;',
                    'src/RemoteEvent/RemoteServiceWebhookConsumer.php' => '#[AsRemoteEventConsumer(\'remote_service\')]',
                ];

                self::assertStringContainsString('created: ', $output);

                foreach ($outputExpectations as $expectedFileName => $expectedContent) {
                    $path = $runner->getPath($expectedFileName);

                    self::assertStringContainsString($expectedFileName, $output);
                    self::assertFileExists($runner->getPath($expectedFileName));
                    self::assertStringContainsString($expectedContent, file_get_contents($path));
                }

                $requestParserSource = file_get_contents($runner->getPath($parserFileName));

                self::assertStringContainsString(
                    'use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;',
                    $requestParserSource
                );

                self::assertStringContainsString(
                    'use Symfony\Component\HttpFoundation\RequestMatcher\PortRequestMatcher;',
                    $requestParserSource
                );

                self::assertStringContainsString(
                    'use Symfony\Component\HttpFoundation\ChainRequestMatcher;',
                    $requestParserSource
                );

                self::assertStringContainsString(
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

        yield 'it_makes_webhook_with_expression_language_injection' => [self::buildMakerTest()
            ->addExtraDependencies('symfony/expression-language')
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    'remote_service',  // webhook name
                    '4',               // 'IsJsonRequestMatcher',
                    '1',               // 'ExpressionRequestMatcher',
                ]);

                self::assertStringContainsString('Success', $output);

                $outputExpectations = [
                    $parserFileName = 'src/Webhook/RemoteServiceRequestParser.php' => 'use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;',
                    'src/RemoteEvent/RemoteServiceWebhookConsumer.php' => '#[AsRemoteEventConsumer(\'remote_service\')]',
                ];

                self::assertStringContainsString('created: ', $output);

                foreach ($outputExpectations as $expectedFileName => $expectedContent) {
                    $path = $runner->getPath($expectedFileName);

                    self::assertStringContainsString($expectedFileName, $output);
                    self::assertFileExists($runner->getPath($expectedFileName));
                    self::assertStringContainsString($expectedContent, file_get_contents($path));
                }

                $requestParserSource = file_get_contents($runner->getPath($parserFileName));

                self::assertStringContainsString(
                    'use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;',
                    $requestParserSource
                );

                self::assertStringContainsString(
                    'use Symfony\Component\HttpFoundation\RequestMatcher\ExpressionRequestMatcher;',
                    $requestParserSource
                );

                self::assertStringContainsString(
                    'use Symfony\Component\HttpFoundation\ChainRequestMatcher;',
                    $requestParserSource
                );

                self::assertStringContainsString(
                    'use Symfony\Component\ExpressionLanguage\Expression;',
                    $requestParserSource
                );

                self::assertStringContainsString(
                    'use Symfony\Component\ExpressionLanguage\ExpressionLanguage;',
                    $requestParserSource
                );

                self::assertStringContainsString(
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

        yield 'it_makes_webhook_not_final' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->writeFile(
                    'config/packages/dev/maker.yaml',
                    Yaml::dump(['when@dev' => ['maker' => ['generate_final_classes' => false]]])
                );

                $runner->runMaker(
                    [
                        'remote_service',
                        '',
                    ]
                );

                $outputExpectations = [
                    'src/Webhook/RemoteServiceRequestParser.php' => 'class RemoteServiceRequestParser extends AbstractRequestParser',
                    'src/RemoteEvent/RemoteServiceWebhookConsumer.php' => 'class RemoteServiceWebhookConsumer implements ConsumerInterface',
                ];

                foreach ($outputExpectations as $expectedFileName => $expectedContent) {
                    $path = $runner->getPath($expectedFileName);

                    self::assertFileExists($runner->getPath($expectedFileName));
                    self::assertStringNotContainsString('final', file_get_contents($path));
                    self::assertStringContainsString($expectedContent, file_get_contents($path));
                }
            }),
        ];
    }
}
