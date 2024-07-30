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
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class MakeCommandRegistrationPass implements CompilerPassInterface
{
    public const MAKER_TAG = 'maker.command';

    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds(self::MAKER_TAG) as $id => $tags) {
            $def = $container->getDefinition($id);
            if ($def->isDeprecated()) {
                continue;
            }

            $class = $container->getParameterBag()->resolveValue($def->getClass());
            if (!is_subclass_of($class, MakerInterface::class)) {
                throw new InvalidArgumentException(\sprintf('Service "%s" must implement interface "%s".', $id, MakerInterface::class));
            }

            $commandDefinition = new ChildDefinition('maker.auto_command.abstract');
            $commandDefinition->setClass(MakerCommand::class);
            $commandDefinition->replaceArgument(0, new Reference($id));

            $tagAttributes = ['command' => $class::getCommandName()];

            if (!method_exists($class, 'getCommandDescription')) {
                // no-op
            } elseif (class_exists(LazyCommand::class)) {
                $tagAttributes['description'] = $class::getCommandDescription();
            } else {
                $commandDefinition->addMethodCall('setDescription', [$class::getCommandDescription()]);
            }

            $commandDefinition->addTag('console.command', $tagAttributes);

            /*
             * @deprecated remove this block when removing make:unit-test and make:functional-test
             */
            if (method_exists($class, 'getCommandAliases')) {
                foreach ($class::getCommandAliases() as $alias) {
                    $commandDefinition->addTag('console.command', ['command' => $alias, 'description' => 'Deprecated alias of "make:test"']);
                }
            }

            /*
             * @deprecated remove this block when removing make:subscriber
             */
            if (method_exists($class, 'getCommandAlias')) {
                $alias = $class::getCommandAlias();
                $commandDefinition->addTag('console.command', ['command' => $alias, 'description' => 'Deprecated alias of "make:listener"']);
            }

            $container->setDefinition(\sprintf('maker.auto_command.%s', Str::asTwigVariable($class::getCommandName())), $commandDefinition);
        }
    }
}
