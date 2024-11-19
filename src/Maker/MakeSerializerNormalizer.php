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
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
final class MakeSerializerNormalizer extends AbstractMaker
{
    public function __construct(private ?FileManager $fileManager = null)
    {
        if (null !== $this->fileManager) {
            @trigger_deprecation(
                'symfony/maker-bundle',
                '1.56.0',
                \sprintf('Initializing MakeSerializerNormalizer while providing an instance of "%s" is deprecated. The $fileManager param will be removed in a future version.', FileManager::class)
            );
        }
    }

    public static function getCommandName(): string
    {
        return 'make:serializer:normalizer';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a new serializer normalizer class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'Choose a class name for your normalizer (e.g. <fg=yellow>UserNormalizer</>)')
            ->setHelp($this->getHelpFileContents('MakeSerializerNormalizer.txt'))
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $normalizerClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Serializer\\Normalizer\\',
            \Normalizer::class
        );

        $useStatements = new UseStatementGenerator([
            NormalizerInterface::class,
            Autowire::class,
            \sprintf('App\Entity\%s', str_replace('Normalizer', '', $normalizerClassNameDetails->getShortName())),
        ]);

        $entityDetails = $generator->createClassNameDetails(
            str_replace('Normalizer', '', $normalizerClassNameDetails->getShortName()),
            'Entity\\',
        );

        if ($entityExists = class_exists($entityDetails->getFullName())) {
            $useStatements->addUseStatement($entityDetails->getFullName());
        }

        $generator->generateClass($normalizerClassNameDetails->getFullName(), 'serializer/Normalizer.tpl.php', [
            'use_statements' => $useStatements,
            'entity_exists' => $entityExists,
            'entity_name' => $entityDetails->getShortName(),
        ]);

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next:',
            '  - Open your new serializer normalizer class and start customizing it.',
            '  - Find the documentation at <fg=yellow>https://symfony.com/doc/current/serializer/custom_normalizer.html</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            Serializer::class,
            'serializer'
        );
    }
}
