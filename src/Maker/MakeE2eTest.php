<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Panthere\PanthereTestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class MakeE2eTest extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:e2e-test';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new end-to-end (E2E) test class')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the end-to-end test class (e.g. <fg=yellow>DefaultControllerTest</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeE2eTest.txt'))
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $testClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Tests\\',
            'Test'
        );

        $generator->generateClass(
            $testClassNameDetails->getFullName(),
            'test/E2e.tpl.php',
            []
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new test class and start customizing it.',
            'Find the documentation at <fg=yellow>https://github.com/symfony/panthere</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            PanthereTestCase::class,
            'symfony/panthere',
            true,
            true
        );
    }
}
