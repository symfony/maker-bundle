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
use Symfony\Bundle\MakerBundle\DependencyInjection\DecoratorHelper;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\DecoratorInfo;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

/**
 * @author Benjamin Georgeault <git@wedgesama.fr>
 */
final class MakeDecorator extends AbstractMaker
{
    public function __construct(
        private readonly DecoratorHelper $helper,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:decorator';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a decorator of a service';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('id', InputArgument::OPTIONAL, 'The ID of the service to decorate.')
            ->addArgument('decorator-class', InputArgument::OPTIONAL, \sprintf('The class name of the service to create (e.g. <fg=yellow>%sDecorator</>)', Str::asClassName(Str::getRandomTerm())))
            ->addOption('priority', null, InputOption::VALUE_REQUIRED, 'The priority of this decoration when multiple decorators are declared for the same service.')
            ->addOption('on-invalid', null, InputOption::VALUE_REQUIRED, 'The behavior to adopt when the decoration is invalid.')
            ->setHelp($this->getHelpFileContents('MakeDecorator.txt'))
        ;

        $inputConfig->setArgumentAsNonInteractive('id');
        $inputConfig->setArgumentAsNonInteractive('decorator-class');
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            AsDecorator::class,
            'dependency-injection',
        );
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        // Ask for service id.
        if (null === $input->getArgument('id')) {
            $argument = $command->getDefinition()->getArgument('id');

            ($question = new Question($argument->getDescription()))
                ->setAutocompleterValues($suggestIds = $this->helper->suggestIds())
                ->setValidator(fn ($answer) => Validator::serviceExists($answer, $suggestIds))
                ->setMaxAttempts(3);

            $input->setArgument('id', $io->askQuestion($question));
        }

        $id = $input->getArgument('id');
        if (null === $realId = $this->helper->getRealId($id)) {
            $guessCount = \count($guessRealIds = $this->helper->guessRealIds($id));

            if (0 === $guessCount) {
                throw new RuntimeCommandException(\sprintf('Cannot find nor guess service for given id "%s".', $id));
            } elseif (1 === $guessCount) {
                $question = new ConfirmationQuestion(\sprintf('<fg=green>Did you mean</> <fg=yellow>"%s"</> <fg=green>?</>', $guessRealIds[0]), true);

                if (!$io->askQuestion($question)) {
                    throw new RuntimeCommandException(\sprintf('Cannot find nor guess service for given id "%s".', $id));
                }

                $input->setArgument('id', $id = $guessRealIds[0]);
            } else {
                $input->setArgument(
                    'id',
                    $id = $io->choice(\sprintf('Multiple services found for "%s", choice which one you want to decorate?', $id), $guessRealIds),
                );
            }
        } else {
            $input->setArgument('id', $id = $realId);
        }

        // Ask for decorator classname.
        if (null === $input->getArgument('decorator-class')) {
            $argument = $command->getDefinition()->getArgument('decorator-class');

            $basename = Str::getShortClassName(match (true) {
                interface_exists($id) => Str::removeSuffix($id, 'Interface'),
                class_exists($id) => $id,
                default => Str::asClassName($id),
            });

            $defaultClass = Str::asClassName(\sprintf('%s Decorator', $basename));

            ($question = new Question($argument->getDescription(), $defaultClass))
                ->setValidator(fn ($answer) => Validator::validateClassName(Validator::classDoesNotExist($answer)))
                ->setMaxAttempts(3);

            $input->setArgument('decorator-class', $io->askQuestion($question));
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $id = $input->getArgument('id');

        $classNameDetails = $generator->createClassNameDetails(
            Validator::validateClassName(Validator::classDoesNotExist($input->getArgument('decorator-class'))),
            '',
        );

        $priority = $input->getOption('priority');
        $onInvalid = $input->getOption('on-invalid');

        $decoratedInfo = new DecoratorInfo(
            $classNameDetails->getFullName(),
            $id,
            $this->helper->getClass($id),
            empty($priority) ? null : $priority,
            null === $onInvalid || 1 === $onInvalid ? null : $onInvalid,
        );

        $classData = $decoratedInfo->getClassData();

        $generator->generateClassFromClassData(
            $classData,
            'decorator/Decorator.tpl.php',
            [
                'decorated_info' => $decoratedInfo,
            ],
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }
}
