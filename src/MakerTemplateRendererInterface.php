<?php

namespace Symfony\Bundle\MakerBundle;

/**
 *
 * @author Jonathan Kablan <jonathan.kablan@gmail.com>
 */
interface MakerTemplateRendererInterface
{
    /**
     * @param string $templateName
     * @return bool
     */
    public function supports(string $templateName): bool;

    /**
     * @param string $templateName
     * @param array $variables
     * @return string
     */
    public function render(string $templateName, array $variables): string;
}