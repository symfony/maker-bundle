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

use Symfony\Bundle\MakerBundle\Maker\MakeScaffold;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Kernel;

class MakeScaffoldTest extends MakerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (Kernel::MAJOR_VERSION < 6) {
            $this->markTestSkipped('Only available on Symfony 6+.');
        }
    }

    public function getTestDetails(): iterable
    {
        foreach (self::scaffoldProvider() as $name) {
            yield $name => [$this->createMakerTest()
                ->preRun(function (MakerTestRunner $runner) {
                    $runner->writeFile('.env.test.local', implode("\n", [
                        'DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"',
                        'MAILER_DSN=null://null',
                    ]));
                })
                ->addExtraDependencies(
                    'process'
                )
                ->run(function (MakerTestRunner $runner) use ($name) {
                    $runner->runMaker([$name]);
                    $runner->runTests();

                    $this->assertTrue(true); // successfully ran tests
                }),
            ];
        }
    }

    protected function getMakerClass(): string
    {
        return MakeScaffold::class;
    }

    private static function scaffoldProvider(): iterable
    {
        foreach (Finder::create()->in(__DIR__.'/../../src/Resources/scaffolds/6.0')->name('*.json') as $file) {
            yield $file->getFilenameWithoutExtension();
        }
    }
}
