<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;

class MainController
{
    public function homepage()
    {
        // create a controller that will make the functional test pass
        return new Response('<html><body><h1>Hello World</h1></body></html>');
    }
}
