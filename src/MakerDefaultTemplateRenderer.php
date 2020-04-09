<?php

namespace Symfony\Bundle\MakerBundle;

class MakerDefaultTemplateRenderer implements MakerTemplateRendererInterface
{
    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @param FileManager $fileManager
     */
    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * @param string $templateName
     * @return bool
     */
    public function supports(string $templateName): bool
    {
        $templatePath = __DIR__.'/Resources/skeleton/'.$templateName;

        return file_exists($templatePath);
    }

    /**
     * @param string $templateName
     * @param array $variables
     * @return string
     */
    public function render(string $templateName, array $variables): string
    {
        $templatePath = __DIR__.'/Resources/skeleton/'.$templateName;

        // this is basically the current logic for rendering templates
        $contents = $this->fileManager->parseTemplate($templatePath, $variables);
        $this->fileManager->dumpFile($templatePath, $contents);

        return $templatePath;
    }
}