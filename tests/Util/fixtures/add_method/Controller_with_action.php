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

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class EmptyController
{
    /**
     * @Route("/action", name="app_action")
     */
    public function action(string $param): JsonResponse
    {
        return new JsonResponse(['param' => $param]);
    }
}
