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

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class Generator
{
    private $fileManager;
    private $pendingOperations = [];

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * Generate a new file for a class from a template.
     */
    public function generateClass(string $className, string $templateName, array $variables): string
    {
        $targetPath = $this->fileManager->getPathForFutureClass($className);

        if (null === $targetPath) {
            throw new \LogicException(sprintf('Could not determine where to locate the new class "%s".', $className));
        }

        $variables = array_merge($variables, [
            'class_name' => Str::getShortClassName($className),
            'namespace' => Str::getNamespace($className),
        ]);

        $this->addOperation($targetPath, $templateName, $variables);

        return $targetPath;
    }

    /**
     * Generate a normal file from a template.
     *
     * @param string $targetPath
     * @param string $templateName
     * @param array $variables
     */
    public function generateFile(string $targetPath, string $templateName, array $variables)
    {
        $this->addOperation($targetPath, $templateName, $variables);
    }

    private function addOperation(string $targetPath, string $templateName, array $variables)
    {
        if ($this->fileManager->fileExists($targetPath)) {
            throw new RuntimeCommandException(sprintf(
                'The file "%s" can\'t be generated because it already exists.',
                $this->fileManager->relativizePath($targetPath)
            ));
        }

        $variables['relative_path'] = $this->fileManager->relativizePath($targetPath);

        $templatePath = $templateName;
        if (!file_exists($templatePath)) {
            $templatePath = __DIR__.'/Resources/skeleton/'.$templateName;

            if (!file_exists($templatePath)) {
                throw new \Exception(sprintf('Cannot find template "%s"', $templateName));
            }
        }

        $this->pendingOperations[$targetPath] = [
            'template' => $templatePath,
            'variables' => $variables,
        ];
    }

    public function hasPendingOperations(): bool
    {
        return !empty($this->pendingOperations);
    }

    public function writeChanges()
    {
        foreach ($this->pendingOperations as $targetPath => $templateData) {
            $templatePath = $templateData['template'];
            $parameters = $templateData['variables'];

            $templateParameters = array_merge($parameters, [
                'relative_path' => $this->fileManager->relativizePath($targetPath),
            ]);

            $fileContents = $this->fileManager->parseTemplate($templatePath, $templateParameters);
            $this->fileManager->dumpFile($targetPath, $fileContents);
        }

        $this->pendingOperations = [];
    }
}
