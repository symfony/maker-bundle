<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Form\AbstractTypeExtension;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
final class MakeFormTypeExtension extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:form:type-extension';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new form type extension class')
            ->addArgument('name', InputArgument::OPTIONAL, sprintf('The name of the form type extension class (e.g. <fg=yellow>%sTypeExtension</>)', Str::asClassName(Str::getRandomTerm())))
            ->addArgument('extended_type', InputArgument::OPTIONAL, 'The name of the extended type class (e.g. <fg=yellow>FormType</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeFormTypeExtension.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
    }

    public function getParameters(InputInterface $input): array
    {
        $typeExtensionClassName = Str::asClassName($input->getArgument('name'), 'TypeExtension');
        Validator::validateClassName($typeExtensionClassName);
        $extendedTypeClassName = $input->getArgument('extended_type');
        Validator::validateClassName($extendedTypeClassName);

        return [
            'type_extension_class_name' => $typeExtensionClassName,
            'extended_type_class_name' => $extendedTypeClassName,
        ];
    }

    public function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/form/TypeExtension.tpl.php' => 'src/Form/Extension/'.$params['type_extension_class_name'].'.php',
        ];
    }

    public function writeSuccessMessage(array $params, ConsoleStyle $io)
    {
        parent::writeSuccessMessage($params, $io);

        $io->text([
            'Next: Make Symfony aware of your form type extension by registering it as a service.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/form/create_form_type_extension.html</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            AbstractTypeExtension::class,
            'form'
        );
    }
}
