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
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
class Generator
{
    private $fileManager;
    private $twigHelper;
    private $pendingOperations = [];
    private $namespacePrefix;

    public function __construct(FileManager $fileManager, $namespacePrefix)
    {
        $this->fileManager = $fileManager;
        $this->twigHelper = new GeneratorTwigHelper($fileManager);
        $this->namespacePrefix = rtrim($namespacePrefix, '\\');
    }

    /**
     * Generate a new file for a class from a template.
     */
    public function generateClass(string $className, string $templateName, array $variables, $force = false): string
    {
        $targetPath = $this->fileManager->getRelativePathForFutureClass($className);

        if (null === $targetPath) {
            throw new \LogicException(sprintf('Could not determine where to locate the new class "%s".', $className));
        }

        $variables = array_merge($variables, [
            'class_name' => Str::getShortClassName($className),
            'namespace' => Str::getNamespace($className),
        ]);

        $this->addOperation($targetPath, $templateName, $variables, $force);

        return $targetPath;
    }

    /**
     * Generate a normal file from a template.
     *
     * @param string $targetPath
     * @param string $templateName
     * @param array  $variables
     */
    public function generateFile(string $targetPath, string $templateName, array $variables, $force = false)
    {
        $variables = array_merge($variables, [
            'helper' => $this->twigHelper,
        ]);

        $this->addOperation($targetPath, $templateName, $variables, $force);
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
     *      // App\Controller\Admin\FooController
     *      $gen->createClassNameDetails('Foo\\Admin', 'Controller', 'Controller');
     *
     *      // App\Controller\Security\Voter\CoolController
     *      $gen->createClassNameDetails('Cool', 'Security\Voter', 'Voter');
     *
     *      // Full class names can also be passed. Imagine the user has an autoload
     *      // rule where Cool\Stuff lives in a "lib/" directory
     *      // Cool\Stuff\BalloonController
     *      $gen->createClassNameDetails('Cool\\Stuff\\Balloon', 'Controller', 'Controller');
     *
     * @param string $name                   The short "name" that will be turned into the class name
     * @param string $namespacePrefix        Recommended namespace where this class should live, but *without* the "App\\" part
     * @param string $suffix                 Optional suffix to guarantee is on the end of the class
     * @param string $validationErrorMessage
     *
     * @return ClassNameDetails
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

        $className = implode('\\', array_map([Str::class, 'addUnderscoreIfPhpKeyword'], explode('\\', $className)));

        Validator::validateClassName($className, $validationErrorMessage);

        // if this is a custom class, we may be completely different than the namespace prefix
        // the best way can do, is find the PSR4 prefix and use that
        if (0 !== strpos($className, $fullNamespacePrefix)) {
            $fullNamespacePrefix = $this->fileManager->getNamespacePrefixForClass($className);
        }

        return new ClassNameDetails($className, $fullNamespacePrefix, $suffix);
    }

    private function addOperation(string $targetPath, string $templateName, array $variables, $force = false)
    {
        if (!$force && $this->fileManager->fileExists($targetPath)) {
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

    /**
     * Actually writes and file changes that are pending.
     */
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
