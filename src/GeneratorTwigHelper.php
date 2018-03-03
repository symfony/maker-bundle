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

/**
 * @author Sadicov Vladimir <sadikoff@gmail.com>
 */
final class GeneratorTwigHelper
{
    private $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function getEntityFieldPrintCode($entity, $field): string
    {
        $printCode = $entity.'.'.$field['fieldName'];

        switch ($field['type']) {
            case 'datetime':
                $printCode .= ' ? '.$printCode.'|date(\'Y-m-d H:i:s\') : \'\'';
                break;
            case 'date':
                $printCode .= ' ? '.$printCode.'|date(\'Y-m-d\') : \'\'';
                break;
            case 'time':
                $printCode .= ' ? '.$printCode.'|date(\'H:i:s\') : \'\'';
                break;
            case 'array':
                $printCode .= ' ? '.$printCode.'|join(\', \') : \'\'';
                break;
            case 'boolean':
                $printCode .= ' ? \'Yes\' : \'No\'';
                break;
        }

        return $printCode;
    }

    public function getHeadPrintCode($title): string
    {
        if ($this->fileManager->fileExists('templates/base.html.twig')) {
            return <<<TWIG
{% extends 'base.html.twig' %}

{% block title %}$title{% endblock %}

TWIG;
        }

        return <<<HTML
<!DOCTYPE html>

<title>$title</title>

HTML;
    }
}
