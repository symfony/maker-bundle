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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class TestingController extends AbstractController
{
    public function homepage()
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return new Response('Homepage Success. Hello: '.$this->getUser()->getUsername());
    }
}
