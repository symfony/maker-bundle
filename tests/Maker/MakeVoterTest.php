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

use Symfony\Bundle\MakerBundle\Maker\MakeVoter;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeVoterTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'voter' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeVoter::class),
            [
                // voter class name
                'FooBar',
            ]),
        ];
    }
}
