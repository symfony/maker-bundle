<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle;

enum NamespaceType: string
{
    case Controller = 'controller';
    case Command = 'command';
    case Entity = 'entity';
    case Form = 'form';
    case Repository = 'repository';

    public function defaultNamespace(): string
    {
        return match ($this) {
            self::Controller => 'Controller',
            self::Command => 'Command',
            self::Entity => 'Entity',
            self::Form => 'Form',
            self::Repository => 'Repository',
        };
    }
}
