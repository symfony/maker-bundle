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

class GeneratorHelper
{
    public function getEntityFieldPrintCode($entity, $field): string
    {
        $printCode = $entity.'.'.$field['fieldName'];

        if (in_array($field['type'], ['datetime'])) {
            $printCode = $printCode.' ? '.$printCode.'|date(\'Y-m-d H:i:s\') : \'\'';
        } elseif (in_array($field['type'], ['date'])) {
            $printCode = $printCode.' ? '.$printCode.'|date(\'Y-m-d\') : \'\'';
        } elseif (in_array($field['type'], ['time'])) {
            $printCode = $printCode.' ? '.$printCode.'|date(\'H:i:s\') : \'\'';
        } elseif (in_array($field['type'], ['array'])) {
            $printCode = $printCode.' ? '.$printCode.'|join(\', \') : \'\'';
        } elseif (in_array($field['type'], ['boolean'])) {
            $printCode = $printCode.' ? \'Yes\' : \'No\'';
        }

        return $printCode;
    }

    public function getHead($baseLayoutExists, $title): string
    {
        if ($baseLayoutExists) {
            $code = <<<TWIG
{% extends 'base.html.twig' %}

{% block title %}$title{% endblock %}

TWIG;
        } else {
            $code = <<<HTML
<!DOCTYPE html>

<html>
    <head>
        <title>$title</title>
    </head>
HTML;
        }

        return $code;
    }

    public function getBodyStart($baseLayoutExists): string
    {
        if ($baseLayoutExists) {
            $code = <<<TWIG
{% block body %}
TWIG;
        } else {
            $code = <<<HTML
    <body>
HTML;
        }

        return $code;
    }

    public function getBodyEnd($baseLayoutExists): string
    {
        if ($baseLayoutExists) {
            $code = <<<TWIG
{% endblock %}
TWIG;
        } else {
            $code = <<<HTML
    </body>
</html>
HTML;
        }

        return $code;
    }
}
