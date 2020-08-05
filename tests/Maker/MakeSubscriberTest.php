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

use Symfony\Bundle\MakerBundle\Maker\MakeSubscriber;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeSubscriberTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'subscriber' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeSubscriber::class),
            [
                // subscriber name
                'FooBar',
                // event name
                'kernel.request',
            ]),
        ];

        yield 'subscriber_unknown_event_class' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeSubscriber::class),
            [
                // subscriber name
                'FooBar',
                // event name
                'foo.unknown_event',
            ]),
        ];
    }
}
