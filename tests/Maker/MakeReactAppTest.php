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

use Symfony\Bundle\MakerBundle\Maker\MakeReactApi;
use Symfony\Bundle\MakerBundle\Maker\MakeReactApp;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeReactAppTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'create_react_app' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeReactApp::class),
            [
                'AppReact',
                'n',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeReactApp')
            ->addExtraDependencies('symfony/webpack-encore-bundle')
            ->addExtraDependencies('twig')
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('Success', $output);

                $this->assertStringContainsString('created: src/Controller/AppReactController.php', $output);
                $this->assertStringContainsString('created: templates/app_react/index.html.twig', $output);
                $this->assertStringContainsString('created: assets/app_react/App.js', $output);
                $this->assertStringContainsString('created: assets/app_react/index.js', $output);
                $this->assertStringContainsString('created: assets/app_react/logo.svg', $output);
                $this->assertStringContainsString('created: assets/app_react/@styles/app.css', $output);

                // only if the user want build a single page application
                $this->assertStringNotContainsString('created: assets/app_react/Api/ApiResource.js', $output);
                $this->assertStringNotContainsString('created: assets/app_react/components/footer.js', $output);
                $this->assertStringNotContainsString('created: assets/app_react/components/header.js', $output);
                $this->assertStringNotContainsString('created: assets/app_react/pages/home.js', $output);
            }),
        ];

        yield 'create_react_app_single_page_application' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeReactApp::class),
            [
                'AppReact',
                'yes',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeReactApp')
            ->addExtraDependencies('symfony/webpack-encore-bundle')
            ->addExtraDependencies('twig')
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('Success', $output);

                $this->assertStringContainsString('created: src/Controller/AppReactController.php', $output);
                $this->assertStringContainsString('created: templates/app_react/index.html.twig', $output);
                $this->assertStringContainsString('created: assets/app_react/App.js', $output);
                $this->assertStringContainsString('created: assets/app_react/index.js', $output);
                $this->assertStringContainsString('created: assets/app_react/Api/ApiResource.js', $output);
                $this->assertStringContainsString('created: assets/app_react/components/footer.js', $output);
                $this->assertStringContainsString('created: assets/app_react/components/header.js', $output);
                $this->assertStringContainsString('created: assets/app_react/pages/home.js', $output);
                $this->assertStringContainsString('created: assets/app_react/logo.svg', $output);
                $this->assertStringContainsString('created: assets/app_react/@styles/app.css', $output);
            }),
        ];

        yield 'create_react_api_resource' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeReactApi::class),
            [
                'app_react',
                'product',
                'Api',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeReactApi')
            ->addExtraDependencies('symfony/webpack-encore-bundle')
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('Success', $output);

                $this->assertStringContainsString('created: assets/app_react/Api/ApiProduct.js', $output);
            }),
        ];
    }
}
