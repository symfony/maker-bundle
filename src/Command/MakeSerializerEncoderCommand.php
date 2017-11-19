<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Command;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Serializer\Serializer;

/**
 * @author Piotr Grabski-Gradzinski <piotr.gradzinski@gmail.com>
 */
final class MakeSerializerEncoderCommand extends AbstractCommand
{
    protected static $defaultName = 'make:serializer:encoder';

    protected function configure()
    {
        $this
            ->setDescription('Creates a new custom encoder class')
            ->addArgument('name', InputArgument::OPTIONAL, 'Choose a class name for your encoder (e.g. <fg=yellow>YamlEncoder</>).')
            ->addArgument('format', InputArgument::OPTIONAL, 'Pick your format name (e.g. <fg=yellow>yaml</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeSerializerEncoder.txt'))
        ;
    }

    protected function getParameters(): array
    {
        $encoderClassName = Str::asClassName($this->input->getArgument('name'), 'Encoder');
        Validator::validateClassName($encoderClassName);
        $format = $this->input->getArgument('format');

        return [
            'encoder_class_name' => $encoderClassName,
            'format' => $format,
        ];
    }

    protected function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/serializer/Encoder.php.txt' => 'src/Serializer/'.$params['encoder_class_name'].'.php'
        ];
    }

    protected function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        $io->text([
            'Next: Open your new encoder class and start customizing it.',
            'Find the documentation at <fg=yellow>http://symfony.com/doc/current/serializer/custom_encoders.html</>'
        ]);
    }

    protected function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
          Serializer::class,
          'serializer'
        );
    }
}
