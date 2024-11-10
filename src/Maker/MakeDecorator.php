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

use Psr\Container\ContainerInterface;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\DecoratorInfo;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

/**
 * @author Benjamin Georgeault <git@wedgesama.fr>
 */
final class MakeDecorator extends AbstractMaker
{
    /**
     * @param array<string> $ids
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $ids,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:decorator';
    }

    public static function getCommandDescription(): string
    {
        return 'Create CRUD for Doctrine entity class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('id', InputArgument::OPTIONAL, 'The ID of the service to decorate.')
            ->addArgument('decorator-class', InputArgument::OPTIONAL, \sprintf('The class name of the service to create (e.g. <fg=yellow>%sDecorator</>)', Str::asClassName(Str::getRandomTerm())))
            ->setHelp($this->getHelpFileContents('MakeDecorator.txt'))
        ;
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
                ->setAutocompleterValues($this->ids)
                ->setValidator(fn ($answer) => Validator::serviceExists($answer, $this->ids))
                ->setMaxAttempts(3);

            $input->setArgument('id', $io->askQuestion($question));
        }

        $id = $input->getArgument('id');

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

        $decoratedInfo = $this->createDecoratorInfo($id, $classNameDetails->getFullName());
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

    private function createDecoratorInfo(string $id, string $decoratorClass): DecoratorInfo
    {
        return new DecoratorInfo(
            $decoratorClass,
            match (true) {
                class_exists($id), interface_exists($id) => $id,
                default => $this->container->get($id)::class,
            },
            $id,
        );
    }
}
