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
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
final class MakeSerializerNormalizer extends AbstractMaker
{
    public function __construct(private FileManager $fileManager)
    {
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
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeSerializerNormalizer.txt'))
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $nextSteps = [];

        $normalizerClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Serializer\\Normalizer\\',
            \Normalizer::class
        );

        $this->generateNormalizer($normalizerClassNameDetails->getFullName(), $generator);

        try {
            $this->configureNormalizerService($normalizerClassNameDetails->getFullName(), $generator);
        } catch (\Throwable) {
            $nextSteps[] = "Your <info>services.yaml</> could not be updated automatically. You'll need to inject the <info>\$objectNormalizer</> argument to manually.";
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        array_push(
            $nextSteps,
            'Open your new serializer normalizer class and start customizing it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/serializer/custom_normalizer.html</>',
        );

        $io->text([
            'Next:',
            ...array_map(static fn (string $s): string => sprintf('  - %s', $s), $nextSteps),
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            Serializer::class,
            'serializer'
        );
    }

    private function generateNormalizer(string $className, Generator $generator): void
    {
        $useStatements = new UseStatementGenerator([
            NormalizerInterface::class,
            CacheableSupportsMethodInterface::class,
        ]);

        $generator->generateClass($className, 'serializer/Normalizer.tpl.php', [
            'use_statements' => $useStatements,
        ]);
    }

    private function configureNormalizerService(string $className, Generator $generator): void
    {
        $servicesFilePath = 'config/services.yaml';

        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents($servicesFilePath));
        $servicesData = $manipulator->getData();

        if (!isset($servicesData['services'][$className])) {
            $servicesData['services'][$className] = [
                'arguments' => [
                    '$objectNormalizer' => '@serializer.normalizer.object',
                ],
            ];
        }

        $manipulator->setData($servicesData);
        $generator->dumpFile($servicesFilePath, $manipulator->getContents());
    }
}
