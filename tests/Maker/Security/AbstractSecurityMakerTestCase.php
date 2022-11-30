<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Maker\Security;

use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
abstract class AbstractSecurityMakerTestCase extends MakerTestCase
{
    protected function makeUser(MakerTestRunner $runner, string $identifier = 'email'): void
    {
        $runner->runConsole('make:user', [
            'User', // Class Name
            'y', // Create as Entity
            $identifier, // Property used to identify the user,
            'y', // Uses a password
        ]);
    }
}
