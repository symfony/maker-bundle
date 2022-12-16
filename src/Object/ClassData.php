<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Object;

use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class ClassData
{
    public function __construct(
        public ClassNameDetails $classNameDetails,
        public string $classPath,
        public bool $classExists,
        public ?ClassSourceManipulator $manipulator = null,
    ) {
        if ($this->classExists) {
            $this->manipulator = new ClassSourceManipulator(file_get_contents($this->classPath));
        }
    }
}
