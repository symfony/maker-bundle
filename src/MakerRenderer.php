<?php


namespace Symfony\Bundle\MakerBundle;

class MakerRenderer implements MakerTemplateRendererInterface
{
    public function supports(string $templateName = null): string
    {
        return $templateName;
    }

    public function render(string $templateName = null): string
    {
        if (null === $templateName) {
            return __DIR__.'/Resources/skeleton/';
        }

        return $this->supports($templateName);
    }
}