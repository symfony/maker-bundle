<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Maker\Security;

use Symfony\Bundle\MakerBundle\Maker\Security\MakeFormLogin;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class MakeFormLoginTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeFormLogin::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'generates_basic_form_login' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([]);

//                $this->assertStringContainsString('Success', $output);
                // @TODO: Use fixtures to ensure actual files generated match expected files

                $this->assertFileExists($runner->getPath('src/Controller/LoginController.php'));
                $this->assertFileExists($runner->getPath('templates/login/login.html.twig'));

                $securityConfig = $runner->readYaml('config/packages/security.yaml');

                $this->assertSame('app_login', $securityConfig['security']['firewalls']['main']['form_login']['login_path']);
                $this->assertSame('app_login', $securityConfig['security']['firewalls']['main']['form_login']['check_path']);
            }),
        ];
    }
}
