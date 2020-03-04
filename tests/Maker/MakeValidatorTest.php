<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeValidator;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeValidatorTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'validator' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeValidator::class),
            [
                // validator name
                'FooBar',
            ]),
        ];
    }
}
