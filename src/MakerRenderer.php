<?php

namespace Symfony\Bundle\MakerBundle;

class MakerRenderer
{
    /**
     * @var MakerTemplateRendererInterface[]
     */
    private $templateRenderers;

    /**
     * @param array $templateRenderers
     */
    public function __construct(array $templateRenderers)
    {
        $this->templateRenderers = $templateRenderers;
    }

    /**
     * @param string $templateName
     * @param array $variables
     * @return string
     * @throws \Exception
     */
    public function renderTemplate(string $templateName, array $variables)
    {
        foreach ($this->templateRenderers as $templateRenderer) {
            if ($templateRenderer->supports($templateName)) {
                return $templateRenderer->render($templateName, $variables);
            }
        }

        throw new \Exception(sprintf('No template renderers for template "%s"', $templateName));
    }
}