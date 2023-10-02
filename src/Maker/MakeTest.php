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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestAssertionsTrait;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputAwareMakerInterface;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\Panther\PantherTestCaseTrait;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeTest extends AbstractMaker implements InputAwareMakerInterface
{
    private const DESCRIPTIONS = [
        'TestCase' => 'basic PHPUnit tests',
        'KernelTestCase' => 'basic tests that have access to Symfony services',
        'WebTestCase' => 'to run browser-like scenarios, but that don\'t execute JavaScript code',
        'ApiTestCase' => 'to run API-oriented scenarios',
        'PantherTestCase' => 'to run e2e scenarios, using a real-browser or HTTP client and a real web server',
    ];
    private const DOCS = [
        'TestCase' => 'https://symfony.com/doc/current/testing.html#unit-tests',
        'KernelTestCase' => 'https://symfony.com/doc/current/testing/database.html#functional-testing-of-a-doctrine-repository',
        'WebTestCase' => 'https://symfony.com/doc/current/testing.html#functional-tests',
        'ApiTestCase' => 'https://api-platform.com/docs/distribution/testing/',
        'PantherTestCase' => 'https://github.com/symfony/panther#testing-usage',
    ];

    public static function getCommandName(): string
    {
        return 'make:test';
    }

    /**
     * @deprecated remove this method when removing make:unit-test and make:functional-test
     */
    public static function getCommandAliases(): iterable
    {
        yield 'make:unit-test';
        yield 'make:functional-test';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new test class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $typesDesc = [];
        $typesHelp = [];
        foreach (self::DESCRIPTIONS as $type => $desc) {
            $typesDesc[] = sprintf('<fg=yellow>%s</> (%s)', $type, $desc);
            $typesHelp[] = sprintf('* <info>%s</info>: %s', $type, $desc);
        }

        $command
            ->addArgument('type', InputArgument::OPTIONAL, 'The type of test: '.implode(', ', $typesDesc))
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the test class (e.g. <fg=yellow>BlogPostTest</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeTest.txt').implode("\n", $typesHelp));

        $inputConfig->setArgumentAsNonInteractive('name');
        $inputConfig->setArgumentAsNonInteractive('type');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        /* @deprecated remove the following block when removing make:unit-test and make:functional-test */
        $this->handleDeprecatedMakerCommands($input, $io);

        if (null !== $type = $input->getArgument('type')) {
            if (!isset(self::DESCRIPTIONS[$type])) {
                throw new RuntimeCommandException(sprintf('The test type must be one of "%s", "%s" given.', implode('", "', array_keys(self::DESCRIPTIONS)), $type));
            }
        } else {
            $input->setArgument(
                'type',
                $io->choice('Which test type would you like?', self::DESCRIPTIONS)
            );
        }

        if ('ApiTestCase' === $input->getArgument('type') && !class_exists(ApiTestCase::class)) {
            $io->warning([
                'API Platform is required for this test type. Install it with',
                'composer require api',
            ]);
        }

        if ('PantherTestCase' === $input->getArgument('type') && !trait_exists(PantherTestCaseTrait::class)) {
            $io->warning([
                'symfony/panther is required for this test type. Install it with',
                'composer require symfony/panther --dev',
            ]);
        }

        if (null === $input->getArgument('name')) {
            $io->writeln([
                '',
                'Choose a class name for your test, like:',
                ' * <fg=yellow>UtilTest</> (to create tests/UtilTest.php)',
                ' * <fg=yellow>Service\\UtilTest</> (to create tests/Service/UtilTest.php)',
                ' * <fg=yellow>\\App\Tests\\Service\\UtilTest</> (to create tests/Service/UtilTest.php)',
            ]);

            $nameArgument = $command->getDefinition()->getArgument('name');
            $value = $io->ask($nameArgument->getDescription(), $nameArgument->getDefault(), [Validator::class, 'notBlank']);
            $input->setArgument($nameArgument->getName(), $value);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $testClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Tests\\',
            'Test'
        );

        $type = $input->getArgument('type');

        $generator->generateClass(
            $testClassNameDetails->getFullName(),
            "test/$type.tpl.php",
            [
                'web_assertions_are_available' => trait_exists(WebTestAssertionsTrait::class),
                'api_test_case_fqcn' => !class_exists(ApiTestCase::class) ? LegacyApiTestCase::class : ApiTestCase::class,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new test class and start customizing it.',
            sprintf('Find the documentation at <fg=yellow>%s</>', self::DOCS[$type]),
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies, InputInterface $input = null): void
    {
        if (null === $input) {
            return;
        }

        switch ($input->getArgument('type')) {
            case 'WebTestCase':
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

                return;

            case 'ApiTestCase':
                $dependencies->addClassDependency(
                    !class_exists(ApiTestCase::class) ? LegacyApiTestCase::class : ApiTestCase::class,
                    'api',
                    true,
                    false
                );

                return;

            case 'PantherTestCase':
                $dependencies->addClassDependency(
                    PantherTestCaseTrait::class,
                    'panther',
                    true,
                    true
                );

                return;
        }
    }

    /**
     * @deprecated
     */
    private function handleDeprecatedMakerCommands(InputInterface $input, ConsoleStyle $io): void
    {
        $currentCommand = $input->getFirstArgument();
        switch ($currentCommand) {
            case 'make:unit-test':
                $input->setArgument('type', 'TestCase');
                $io->warning('The "make:unit-test" command is deprecated, use "make:test" instead.');
                break;

            case 'make:functional-test':
                $input->setArgument('type', trait_exists(PantherTestCaseTrait::class) ? 'WebTestCase' : 'PantherTestCase');
                $io->warning('The "make:functional-test" command is deprecated, use "make:test" instead.');
                break;
        }
    }
}
