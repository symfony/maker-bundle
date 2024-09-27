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

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

trigger_deprecation('symfony/maker-bundle', '1.29', 'The "%s" class is deprecated, use "%s" instead.', MakeUnitTest::class, MakeTest::class);

/**
 * @deprecated since MakerBundle 1.29, use Symfony\Bundle\MakerBundle\Maker\MakeTest instead.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeUnitTest extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:unit-test';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a new unit test class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the unit test class (e.g. <fg=yellow>UtilTest</>)')
            ->setHelp($this->getHelpFileContents('MakeUnitTest.txt'))
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $testClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Tests\\',
            'Test'
        );

        $generator->generateClass(
            $testClassNameDetails->getFullName(),
            'test/Unit.tpl.php',
            ['use_statements' => new UseStatementGenerator([TestCase::class])]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new test class and start customizing it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/testing.html#unit-tests</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }
}
