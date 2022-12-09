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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\PhpCompatUtil;
use Symfony\Bundle\MakerBundle\Util\TemplateComponentGenerator;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
class Generator
{
    private GeneratorTwigHelper $twigHelper;
    private array $pendingOperations = [];
    private ?TemplateComponentGenerator $templateComponentGenerator;
    private array $generatedFiles = [];

    public function __construct(
        private FileManager $fileManager,
        private string $namespacePrefix,
        PhpCompatUtil $phpCompatUtil = null,
        TemplateComponentGenerator $templateComponentGenerator = null,
    ) {
        $this->twigHelper = new GeneratorTwigHelper($fileManager);
        $this->namespacePrefix = trim($namespacePrefix, '\\');

        if (null !== $phpCompatUtil) {
            trigger_deprecation('symfony/maker-bundle', 'v1.44.0', 'Initializing Generator while providing an instance of PhpCompatUtil is deprecated.');
        }

        $this->templateComponentGenerator = $templateComponentGenerator;
    }

    /**
     * Generate a new file for a class from a template.
     *
     * @param string $className    The fully-qualified class name
     * @param string $templateName Template name in Resources/skeleton to use
     * @param array  $variables    Array of variables to pass to the template
     *
     * @return string The path where the file will be created
     *
     * @throws \Exception
     */
    public function generateClass(string $className, string $templateName, array $variables = []): string
    {
        $targetPath = $this->fileManager->getRelativePathForFutureClass($className);

        if (null === $targetPath) {
            throw new \LogicException(sprintf('Could not determine where to locate the new class "%s", maybe try with a full namespace like "\\My\\Full\\Namespace\\%s"', $className, Str::getShortClassName($className)));
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
     * @return void
     */
    public function generateFile(string $targetPath, string $templateName, array $variables = [])
    {
        $variables = array_merge($variables, [
            'helper' => $this->twigHelper,
        ]);

        $this->addOperation($targetPath, $templateName, $variables);
    }

    /**
     * @return void
     */
    public function dumpFile(string $targetPath, string $contents)
    {
        $this->pendingOperations[$targetPath] = [
            'contents' => $contents,
        ];
    }

    public function getFileContentsForPendingOperation(string $targetPath): string
    {
        if (!isset($this->pendingOperations[$targetPath])) {
            throw new RuntimeCommandException(sprintf('File "%s" is not in the Generator\'s pending operations', $targetPath));
        }

        $templatePath = $this->pendingOperations[$targetPath]['template'];
        $parameters = $this->pendingOperations[$targetPath]['variables'];

        $templateParameters = array_merge($parameters, [
            'relative_path' => $this->fileManager->relativizePath($targetPath),
        ]);

        return $this->fileManager->parseTemplate($templatePath, $templateParameters);
    }

    /**
     * Creates a helper object to get data about a class name.
     *
     * Examples:
     *
     *      // App\Entity\FeaturedProduct
     *      $gen->createClassNameDetails('FeaturedProduct', 'Entity');
     *      $gen->createClassNameDetails('featured product', 'Entity');
     *
     *      // App\Controller\FooController
     *      $gen->createClassNameDetails('foo', 'Controller', 'Controller');
     *
     *      // App\Controller\Foo\AdminController
     *      $gen->createClassNameDetails('Foo\\Admin', 'Controller', 'Controller');
     *
     *      // App\Security\Voter\CoolVoter
     *      $gen->createClassNameDetails('Cool', 'Security\Voter', 'Voter');
     *
     *      // Full class names can also be passed. Imagine the user has an autoload
     *      // rule where Cool\Stuff lives in a "lib/" directory
     *      // Cool\Stuff\BalloonController
     *      $gen->createClassNameDetails('Cool\\Stuff\\Balloon', 'Controller', 'Controller');
     *
     * @param string $name            The short "name" that will be turned into the class name
     * @param string $namespacePrefix Recommended namespace where this class should live, but *without* the "App\\" part
     * @param string $suffix          Optional suffix to guarantee is on the end of the class
     */
    public function createClassNameDetails(string $name, string $namespacePrefix, string $suffix = '', string $validationErrorMessage = ''): ClassNameDetails
    {
        $fullNamespacePrefix = $this->namespacePrefix.'\\'.$namespacePrefix;
        if ('\\' === $name[0]) {
            // class is already "absolute" - leave it alone (but strip opening \)
            $className = substr($name, 1);
        } else {
            $className = rtrim($fullNamespacePrefix, '\\').'\\'.Str::asClassName($name, $suffix);
        }

        Validator::validateClassName($className, $validationErrorMessage);

        // if this is a custom class, we may be completely different than the namespace prefix
        // the best way can do, is find the PSR4 prefix and use that
        if (!str_starts_with($className, $fullNamespacePrefix)) {
            $fullNamespacePrefix = $this->fileManager->getNamespacePrefixForClass($className);
        }

        return new ClassNameDetails($className, $fullNamespacePrefix, $suffix);
    }

    public function getRootDirectory(): string
    {
        return $this->fileManager->getRootDirectory();
    }

    public function hasPendingOperations(): bool
    {
        return !empty($this->pendingOperations);
    }

    /**
     * Actually writes and file changes that are pending.
     *
     * @return void
     */
    public function writeChanges()
    {
        foreach ($this->pendingOperations as $targetPath => $templateData) {
            $this->generatedFiles[] = $targetPath;

            if (isset($templateData['contents'])) {
                $this->fileManager->dumpFile($targetPath, $templateData['contents']);

                continue;
            }

            $this->fileManager->dumpFile(
                $targetPath,
                $this->getFileContentsForPendingOperation($targetPath)
            );
        }

        $this->pendingOperations = [];
    }

    public function getRootNamespace(): string
    {
        return $this->namespacePrefix;
    }

    public function generateController(string $controllerClassName, string $controllerTemplatePath, array $parameters = []): string
    {
        return $this->generateClass(
            $controllerClassName,
            $controllerTemplatePath,
            $parameters +
            [
                'generator' => $this->templateComponentGenerator,
            ]
        );
    }

    /**
     * Generate a template file.
     *
     * @return void
     */
    public function generateTemplate(string $targetPath, string $templateName, array $variables = [])
    {
        $this->generateFile(
            $this->fileManager->getPathForTemplate($targetPath),
            $templateName,
            $variables
        );
    }

    /**
     * Get the full path of each file created by the Generator.
     */
    public function getGeneratedFiles(): array
    {
        return $this->generatedFiles;
    }

    /**
     * @deprecated MakerBundle only supports AbstractController::class. This method will be removed in the future.
     */
    public static function getControllerBaseClass(): ClassNameDetails
    {
        trigger_deprecation('symfony/maker-bundle', 'v1.41.0', 'MakerBundle only supports AbstractController. This method will be removed in the future.');

        return new ClassNameDetails(AbstractController::class, '\\');
    }

    private function addOperation(string $targetPath, string $templateName, array $variables): void
    {
        if ($this->fileManager->fileExists($targetPath)) {
            throw new RuntimeCommandException(sprintf('The file "%s" can\'t be generated because it already exists.', $this->fileManager->relativizePath($targetPath)));
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
}
