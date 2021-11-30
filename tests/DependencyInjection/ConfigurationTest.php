<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indragunawan\ApiRateLimitBundle\Tests\DependencyInjection;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Processor
     */
    private $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->configuration = new Configuration();
        $this->processor = new Processor();
    }

    protected function tearDown(): void
    {
        $this->configuration = null;
        $this->processor = null;
    }

    public function testDefaultConfig()
    {
        $config = $this->processor->processConfiguration(
            $this->configuration,
            [
                [],
            ]
        );

        $this->assertSame('App', $config['root_namespace']);
        $this->assertEmpty($config['custom_type_hints']);
    }

    public function testCustomTypeHints()
    {
        $config = $this->processor->processConfiguration(
            $this->configuration,
            [
                [
                    'custom_type_hints' => [
                        'my_type' => DateTimeImmutable::class,
                    ],
                ],
            ]
        );

        $this->assertSame(['my_type' => DateTimeImmutable::class], $config['custom_type_hints']);
    }
}
