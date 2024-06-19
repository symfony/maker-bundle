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

use Symfony\Bundle\FrameworkBundle\Test\WebTestAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\PantherTestCaseTrait;

trigger_deprecation('symfony/maker-bundle', '1.29', 'The "%s" class is deprecated, use "%s" instead.', MakeFunctionalTest::class, MakeTest::class);

/**
 * @deprecated since MakerBundle 1.29, use Symfony\Bundle\MakerBundle\Maker\MakeTest instead.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
class MakeFunctionalTest extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:functional-test';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a new functional test class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the functional test class (e.g. <fg=yellow>DefaultControllerTest</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeFunctionalTest.txt'))
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $testClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Tests\\',
            'Test'
        );

        $pantherAvailable = trait_exists(PantherTestCaseTrait::class);

        $useStatements = new UseStatementGenerator([
            $pantherAvailable ? PantherTestCase::class : WebTestCase::class,
        ]);

        $generator->generateClass(
            $testClassNameDetails->getFullName(),
            'test/Functional.tpl.php',
            [
                'use_statements' => $useStatements,
                'web_assertions_are_available' => trait_exists(WebTestAssertionsTrait::class),
                'panther_is_available' => $pantherAvailable,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new test class and start customizing it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/testing.html#functional-tests</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            History::class,
            'browser-kit',
            true,
            true
        );
        $dependencies->addClassDependency(
            CssSelectorConverter::class,
            'css-selector',
            true,
            true
        );
    }
}
