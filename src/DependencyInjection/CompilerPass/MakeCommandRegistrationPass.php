<?php

namespace Symfony\Bundle\MakerBundle\DependencyInjection\CompilerPass;

use Symfony\Bundle\MakerBundle\Command\MakerCommand;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class MakeCommandRegistrationPass implements CompilerPassInterface
{
    const MAKER_TAG = 'maker.command';

    public function process(ContainerBuilder $container)
    {
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
                ServiceLocatorTagPass::register($container, [
                    'maker' => new Reference($id),
                ]),
                $class,
                new Reference('maker.generator'),
            ])->addTag('console.command', ['command' => $class::getCommandName()]);
        }
    }
}
