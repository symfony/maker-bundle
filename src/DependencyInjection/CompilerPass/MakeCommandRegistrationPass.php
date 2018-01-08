<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\DependencyInjection\CompilerPass;

use Symfony\Bundle\MakerBundle\Command\MakerCommand;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Form\Extension\Core\CoreExtension;

class MakeCommandRegistrationPass implements CompilerPassInterface
{
    const MAKER_TAG = 'maker.command';

    public function process(ContainerBuilder $container)
    {
        $this->processFormTypeExtensionMaker($container);

        foreach ($container->findTaggedServiceIds(self::MAKER_TAG) as $id => $tags) {
            $def = $container->getDefinition($id);
            $class = $container->getParameterBag()->resolveValue($def->getClass());
            if (!is_subclass_of($class, MakerInterface::class)) {
                throw new InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, MakerInterface::class));
            }

            $container->register(
                sprintf('maker.auto_command.%s', Str::asTwigVariable($class::getCommandName())),
                MakerCommand::class
            )->setArguments([
                new Reference($id),
                new Reference('maker.file_manager'),
            ])->addTag('console.command', ['command' => $class::getCommandName()]);
        }
    }

    private function processFormTypeExtensionMaker(ContainerBuilder $container)
    {
        if (!class_exists(CoreExtension::class)) {
            return;
        }

        $typesMap = [];
        // add core form types
        $coreExtension = new CoreExtension();
        $loadTypesRefMethod = (new \ReflectionObject($coreExtension))->getMethod('loadTypes');
        $loadTypesRefMethod->setAccessible(true);
        $coreTypes = $loadTypesRefMethod->invoke($coreExtension);
        foreach ($coreTypes as $type) {
            $fqcn = \get_class($type);
            $cn = \array_slice(explode('\\', $fqcn), -1)[0];
            $typesMap[$cn] = $fqcn;
        }

        // add form type services
        foreach ($container->findTaggedServiceIds('form.type', true) as $serviceId => $tag) {
            $fqcn = $container->getDefinition($serviceId)->getClass();
            $cn = \array_slice(explode('\\', $fqcn), -1)[0];
            if (isset($typesMap[$cn])) {
                if (!\in_array($fqcn, (array) $typesMap[$cn], true)) {
                    // preparing for ambiguous question
                    $typesMap[$cn] = array_merge((array) $typesMap[$cn], [$fqcn]);
                }
            } else {
                $typesMap[$cn] = $fqcn;
            }
        }

        $maker = $container->getDefinition('maker.maker.make_form_type_extension');
        $maker->setArgument(0, $typesMap);
    }
}
