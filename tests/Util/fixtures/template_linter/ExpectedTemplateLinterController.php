<?php

namespace Symfony\Bundle\MakerBundle\Tests\Util\fixtures\source;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class TemplateLinterController extends AbstractController
{
    public function index(): Response
    {
        return $this->render('some/template.html.twig');
    }
}
