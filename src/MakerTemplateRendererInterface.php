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
     * @return string
     */
    public function supports(string $templateName): string;

    /**
     * @param string $templateName
     * @return string
     */
    public function render(string $templateName = null): string;
}