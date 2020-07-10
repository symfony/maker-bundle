<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Util;

use PhpParser\Builder\Namespace_;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;

/**
 * This class allows to parse and convert the YAML service configuration file to PHP format.
 *
 * @author Antoine Michelet <jean.marcel.michelet@gmail.com>
 *
 * @internal
 */
final class PhpServicesCreator
{
    private $factory;
    private $stmts = [];
    private $useStatements = [];

    public function __construct()
    {
        $this->factory = new BuilderFactory();
    }

    /**
     * Converts the source YAML into PHP services config
     *
     * If $environmentFiles is specified, it should be an array where
     * the key is an environment and the value is a relative path to
     * a file that should be imported in that environment.
     */
    public function convert(string $yaml, array $environmentFiles = []): string
    {
        $creatorFactory = $this->factory->namespace('Symfony\Component\DependencyInjection\Loader\Configurator');

        // for the Kernel type-hint
        $this->useStatements[] = 'App\\Kernel';

        $closureStmt = $this->createClosureStmts(
            (new Parser())->parse($yaml, Yaml::PARSE_CUSTOM_TAGS),
            $creatorFactory,
            $environmentFiles
        );

        $creatorFactory->addStmt(
            new Node\Stmt\Return_(
                new Node\Expr\Closure([
                    'params' => [
                        (new Param('configurator'))->setType('ContainerConfigurator')->getNode(),
                        (new Param('kernel'))->setType('Kernel')->getNode(),
                    ],
                    'stmts' => $closureStmt,
                    'returnType' => 'void',
                ])
            )
        );

        /** @var Node\Stmt\Namespace_ $node */
        $node = $creatorFactory->getNode();
        $beforeClosure = !empty($this->useStatements) ? "\n" : null;
        // add a blank line between the last use statement and the closure
        array_splice($node->stmts,
            -1,
            0,
            [new Node\Name($beforeClosure."/*\n * This file is the entry point to configure your own services.\n */")]
        );

        return (new PrettyPrinter())->prettyPrintFile([$node])."\n";
    }

    /**
     * @return Node[]
     */
    private function createClosureStmts(array $yamlData, Namespace_ $creatorFactory, array $environmentFiles): array
    {
        foreach ($yamlData as $key => $values) {
            if (null === $values) {
                // declare the variable ($parameters/$services) even if the key is written without values.
                $values = [];
            }
            switch ($key) {
                case 'parameters':
                    $this->addParametersNodes($values);
                    break;
                case 'imports':
                    $this->addImportsNodes($values);
                    break;
                case 'services':
                    $this->addServicesNodes($values);
                    break;
                default:
                    throw new \LogicException(sprintf('The key %s is not supported by the converter: only service config can live in services.yaml for conversion.', $key));
                    break;
            }
        }

        foreach ($environmentFiles as $environment => $path) {
            $this->addEnvironmentImport($environment, $path);
        }

        sort($this->useStatements);
        foreach ($this->useStatements as $className) {
            $creatorFactory->addStmt($this->factory->use($className));
        }

        // remove the last carriage return "\n" if exists.
        $lastStmt = $this->stmts[array_key_last($this->stmts)];
        if ($lastStmt instanceof Node\Name) {
            $lastStmt->parts[0] = rtrim($lastStmt->parts[0], "\n");
        }

        return $this->stmts;
    }

    private function addParametersNodes(array $parameters)
    {
        $this->addLineStmt('// Put parameters here that don\'t need to change on each machine where the app is deployed.');
        $this->addLineStmt('// https://symfony.com/doc/current/best_practices/configuration.html#application-related-configuration.');

        $this->addLineStmt('$parameters = $configurator->parameters();', true);

        foreach ($parameters as $parameterName => $value) {
            $this->addLineStmt(sprintf(
                '$parameters->set(%s, %s);',
                $this->createStringArgument($parameterName),
                $this->toString($value, true)
            ), true);
        }
    }

    private function addImportsNodes(array $imports)
    {
        foreach ($imports as $import) {
            if (\is_array($import)) {
                $arguments = $this->sortArgumentsByKeyIfExists($import, ['resource', 'type', 'ignore_errors']);

                $import = $this->createListFromArray($arguments);
            } else {
                $import = $this->createStringArgument($import);
            }

            $this->addLineStmt(sprintf(
                '$configurator->import(%s);',
                $import
            ), true);
        }
    }

    private function addServicesNodes(array $services)
    {
        $this->addLineStmt('$services = $configurator->services();', true);

        foreach ($services as $serviceKey => $serviceValues) {
            if ('_defaults' === $serviceKey) {
                $this->addLineStmt('// default configuration for services in *this* file');
                $this->addNode($this->factory->methodCall(new Node\Expr\Variable('services'), 'defaults'));

                $this->convertServiceOptionsToNodes($serviceValues);
                $this->addLineStmt(';', true);

                continue;
            }

            if ('_instanceof' === $serviceKey) {
                foreach ($serviceValues as $instanceKey => $instanceValues) {
                    $className = $this->addUseStatementIfNecessary($instanceKey);

                    $this->addLineStmt(sprintf('$services->instanceof(%s)', $className));

                    $this->convertServiceOptionsToNodes($instanceValues);

                    $this->addLineStmt(';', true);
                }

                continue;
            }

            if (isset($serviceValues['resource'])) {
                // Due to the yaml behavior that does not allow the declaration of several identical key names.
                if (isset($serviceValues['namespace'])) {
                    $serviceKey = $serviceValues['namespace'];
                    unset($serviceValues['namespace']);
                }

                if ('App\\' === $serviceKey) {
                    $this->addLineStmt('// makes classes in src/ available to be used as services');
                    $this->addLineStmt('// this creates a service per class whose id is the fully-qualified class name');
                }

                if ('App\\Controller\\' === $serviceKey) {
                    $this->addLineStmt('// controllers are imported separately to make sure services can be injected');
                    $this->addLineStmt('// as action arguments even if you don\'t extend any base controller class');
                }

                $loadMethod = '$services->load(%s, %s)';
                $loadMethod .= 1 === \count($serviceValues) ? ';' : '';

                $this->addLineStmt(sprintf($loadMethod,
                    $this->createStringArgument($serviceKey),
                    '__DIR__.' . $this->createStringArgument('/'.$serviceValues['resource']))
                );

                if (\count($serviceValues) > 1) {
                    unset($serviceValues['resource']);

                    $this->convertServiceOptionsToNodes($serviceValues);
                    $this->addLineStmt(';', true);
                }

                continue;
            }

            if ($this->isAlias($serviceKey, $serviceValues)) {
                $this->addAliasNode($serviceKey, $serviceValues);

                continue;
            }

            if (isset($serviceValues['class'])) {
                $className = $this->addUseStatementIfNecessary($serviceValues['class']);

                $this->addLineStmt(sprintf(
                    '$services->set(%s, %s)',
                    $this->createStringArgument($serviceKey),
                    $className
                ));

                unset($serviceValues['class']);
                $this->convertServiceOptionsToNodes($serviceValues);
                $this->addLineStmt(';', true);

                continue;
            }

            $className = $this->addUseStatementIfNecessary($serviceKey);

            if (null === $serviceValues) {
                $this->addLineStmt(sprintf('$services->set(%s);', $className), true);
            } else {
                $this->addLineStmt(sprintf('$services->set(%s)', $className));
                $this->convertServiceOptionsToNodes($serviceValues);
                $this->addLineStmt(';', true);
            }
        }
    }

    private function convertServiceOptionsToNodes(array $servicesValues)
    {
        foreach ($servicesValues as $serviceConfigKey => $value) {
            // options started by decoration_<option> are used as options of the method decorate().
            if (strstr($serviceConfigKey, 'decoration_')) {
                continue;
            }

            switch ($serviceConfigKey) {
                case 'decorates':
                    $this->addLineStmt($this->createDecorateMethod($servicesValues));

                    break;

                case 'deprecated':
                    $this->addLineStmt($this->createDeprecateMethod($value));

                    break;

                case 'exclude':
                    $this->addLineStmt($this->createMethod('exclude', $this->toString($value)));
                    break;

                // simple "key: value" options
                case 'shared':
                case 'public':
                case 'autowire':
                case 'autoconfigure':
                    if (\is_array($value)) {
                        throw new \InvalidArgumentException(sprintf('The "%s" service option does not support being set to an array value.', $serviceConfigKey));
                    }

                    $method = $serviceConfigKey;
                    if ('shared' === $serviceConfigKey) {
                        $method = 'share';
                    }

                    $this->addLineStmt($this->createMethod($method, $this->toString($value)));

                    break;

                case 'factory':
                case 'configurator':
                    if (\is_array($value) && $this->isAssociativeArray($value)) {
                        throw new \InvalidArgumentException(sprintf('The config key "%s" does not support an associative array', $serviceConfigKey));
                    }

                    $this->addLineStmt($this->createMethod(
                        $serviceConfigKey,
                        // the handles converting all formats of the single arg
                        $this->toString($value)
                    ));

                    break;

                case 'tags':
                    if (\is_array($value) && $this->isAssociativeArray($value)) {
                        throw new \InvalidArgumentException('Unexpected associative array value for "tags"');
                    }

                    foreach ($value as $argValue) {
                        $this->addLineStmt($this->createTagMethod($argValue));
                    }

                    break;

                case 'calls':
                    if ($this->isAssociativeArray($value)) {
                        throw new \InvalidArgumentException('Unexpected associative array for "calls" config.');
                    }

                    foreach ($value as $argValue) {
                        $this->addLineStmt($this->createCallMethod($argValue));
                    }

                    break;

                case 'arguments':
                    if ($this->isAssociativeArray($value)) {
                        foreach ($value as $argKey => $argValue) {
                            $this->addLineStmt($this->createMethod(
                                'arg',
                                $this->createStringArgument($argKey),
                                $this->toString($argValue)
                            ));
                        }
                    } else {
                        $this->addLineStmt($this->createMethod(
                            'args',
                            $this->createSequentialArray($value))
                        );
                    }

                    break;

                default:
                    throw new \InvalidArgumentException(sprintf('Unexpected service configuration option: "%s".', $serviceConfigKey));
            }

            // was used for sequential arrays
            // $this->addLineStmt($this->createMethod($method, $this->createSequentialArray($value)));
        }
    }

    private function addAliasNode($serviceKey, $serviceValues)
    {
        if ($fullClassName = strstr($serviceKey, ' $', true)) {
            $argument = strstr($serviceKey, '$');
            $shortClassName = $this->addUseStatementIfNecessary($fullClassName).".' ".$argument."'";
            // extract '@' before calling method createStringArgument() because service() is not necessary.
            $alias = $this->createStringArgument(substr($serviceValues, 1));

            $this->addLineStmt(sprintf('$services->alias(%s, %s);',
                $shortClassName,
                $alias
            ), true);

            return;
        }

        if (isset($serviceValues['alias'])) {
            $className = $this->addUseStatementIfNecessary($serviceValues['alias']);

            $this->addLineStmt(sprintf(
                '$services->alias(%s, %s)',
                $this->createStringArgument($serviceKey),
                $className
            ));

            unset($serviceValues['alias']);
        }

        if (\is_string($serviceValues) && '@' === $serviceValues[0]) {
            $className = $this->addUseStatementIfNecessary($serviceKey);
            $value = $this->createStringArgument(substr($serviceValues, 1));
            $this->addLineStmt(sprintf('$services->alias(%s, %s);', $className, $value), true);
        }

        if (\is_array($serviceValues)) {
            $this->convertServiceOptionsToNodes($serviceValues);
            $this->addLineStmt(';', true);
        }
    }

    private function createMethod(string $method, ...$argumentStrings): string
    {
        if ('public' === $method && 'false' === $argumentStrings[0]) {
            $method = 'private';
            $argumentStrings[0] = null;
        }

        if ('arguments' === $method) {
            $method = 'args';
        }

        return self::tab().sprintf('->%s(%s)', $method, implode(', ', $argumentStrings));
    }

    private function createDecorateMethod(array $value): string
    {
        $arguments = $this->sortArgumentsByKeyIfExists($value, [
            'decoration_inner_name' => null,
            'decoration_priority' => 0,
            'decoration_on_invalid' => null,
        ]);

        if (isset($arguments['decoration_on_invalid'])) {
            $this->addUseStatementIfNecessary(ContainerInterface::class);
            $arguments['decoration_on_invalid'] = 'exception' === $arguments['decoration_on_invalid']
                ? 'ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE'
                : 'ContainerInterface::IGNORE_ON_INVALID_REFERENCE';
        }

        // Don't write the next arguments if they are null.
        if (null === $arguments['decoration_on_invalid'] && 0 === $arguments['decoration_priority']) {
            unset($arguments['decoration_on_invalid'], $arguments['decoration_priority']);

            if (null === $arguments['decoration_inner_name']) {
                unset($arguments['decoration_inner_name']);
            }
        }
        array_unshift($arguments, $value['decorates']);

        return sprintf(self::tab().'->decorate(%s)', $this->createListFromArray($arguments));
    }

    private function createCallMethod($values): string
    {
        if (isset($values['method'])) {
            $method = $values['method'];

            if (isset($values['arguments'])) {
                $values[1] = $values['arguments'];
            }

            if (isset($values['returns_clone'])) {
                $values[2] = $values['returns_clone'];
            }
        } elseif (\is_string(array_key_first($values))) {
            // if the first index is a string, it means that it is the name of the called method.
            $method = array_key_first($values);
        } else {
            // else, the name of the method is the value.
            $method = $values[0];
        }

        if (isset($values['withLogger'])) {
            $values[1] = $values['withLogger']->getValue();
            $values[2] = 'returns_clone' === $values['withLogger']->getTag();
        } elseif (isset($values[$method])) {
            $values[1] = $values[$method];
        }

        $value = sprintf(self::tab().'->call(%s, %s',
            $this->createStringArgument($method),
            $this->toString($values[1])
        );

        if (isset($values[2]) && \is_bool($values[2])) {
            $value .= $values[2] ? ', true' : ', false';
        }

        return $value.')';
    }

    private function createTagMethod($value): string
    {
        if (!\is_array($value)) {
            $value = ['name' => $value];
        }

        $name = $this->toString($value['name']);
        unset($value['name']);

        if (empty($value)) {
            return $this->createMethod('tag', $name);
        }

        return $this->createMethod('tag', $name, $this->createAssociativeArray($value));
    }

    private function createDeprecateMethod($value): string
    {
        // the old, simple format
        if (!\is_array($value)) {
            return $this->createMethod('deprecate', $this->toString($value));
        }

        $package = $value['package'] ?? '';
        $version = $value['version'] ?? '';
        $message = $value['message'] ?? '';

        return $this->createMethod(
            'deprecate',
            $this->toString($package),
            $this->toString($version),
            $this->toString($message)
        );
    }

    /**
     * @param $value
     */
    private function toString($value, bool $preserveValueIfTrueBoolean = false): string
    {
        if ($value instanceof TaggedValue) {
            return $this->createTaggedValue($value);
        }

        switch (\gettype($value)) {
            case 'array':
                return $this->createArrayArgument($value);
                break;
            case 'string':
                return strstr($value, '::') ? $value : $this->createStringArgument($value);
                break;
            case 'boolean':
                // The convertor preserve the boolean values only if it's necessary. >>>
                if ($preserveValueIfTrueBoolean && true === $value) {
                    return 'true';
                }
                // >>> Because most of the methods don't need to pass true as an argument.
                return true === $value ? '' : 'false';
                break;
            case 'NULL':
                return 'null';
            default:
                return $value;
        }
    }

    private function createTaggedValue($value): string
    {
        if ('service' === $value->getTag()) {
            $className = $this->addUseStatementIfNecessary($value->getValue()['class']);

            return 'inline_service('.$className.')';
        }

        if (\is_array($value->getValue())) {
            $argumentsInOrder = $this->sortArgumentsByKeyIfExists(
                $value->getValue(),
                ['tag', 'index_by', 'default_index_method']
            );

            $args = $this->createListFromArray($argumentsInOrder);
        } else {
            $args = $this->toString($value->getValue());
        }

        return $value->getTag().'('.$args.')';
    }

    private function createArrayArgument(array $values): string
    {
        return $this->isAssociativeArray($values)
            ? $this->createAssociativeArray($values)
            : $this->createSequentialArray($values);
    }

    private function createSequentialArray(array $array, bool $transformInList = false): string
    {
        if ($this->indentationIsRequired($array)) {
            $stringArray = "\n";
            foreach ($array as $subKey => $subValue) {
                $stringArray .= self::tab(3).$this->toString($subValue, true).",\n";
            }

            if ($transformInList) {
                $stringArray = substr_replace($stringArray, '', -2, 1);
            }
            $stringArray .= self::tab(2);

            return $transformInList ? $stringArray : '['.$stringArray.']';
        }

        $stringArray = $transformInList ? '' : '[';

        foreach ($array as $key => $value) {
            $stringArray .= $this->toString($value, true).', ';
        }
        $stringArray = rtrim($stringArray, ', ');

        return $transformInList ? $stringArray : $stringArray.']';
    }

    private function createAssociativeArray(array $array): string
    {
        if ($this->indentationIsRequired($array)) {
            $stringArray = "[\n";
            foreach ($array as $key => $value) {
                $stringArray .= self::tab(4).$this->toString($key).' => '.$this->toString($value, true);
                $stringArray .= \count($array) > 1 ? ",\n" : "\n";
            }

            return $stringArray.self::tab(3).']';
        }

        $stringArray = '[';
        foreach ($array as $key => $value) {
            $stringArray .= $this->toString($key).' => '.$this->toString($value, true);
            $stringArray .= next($array) ? ', ' : '';
        }

        return $stringArray.']';
    }

    private function createListFromArray(array $array): string
    {
        return $this->createSequentialArray($array, $transformInList = true);
    }

    private function createStringArgument(string $value): string
    {
        list($expression, $value) = $this->extractStringValue($value);

        if ('expr' === $expression) {
            if (strstr($value, '"')) {
                return 'expr(\''.str_replace('\\\\', '\\', $value).'\')';
            }

            return 'expr("'.$value.'")';
        }

        if (class_exists($value) || interface_exists($value)) {
            $value = $this->addUseStatementIfNecessary($value);
            if ('ref' === $expression) {
                return 'service('.$value.')';
            }
        }

        if ('ref' === $expression) {
            return "service('".$value."')";
        }

        return $value;
    }

    /**
     * @param array $inOrderKeys Pass an array of keys to sort if exists
     *                           or an associative array following this logic [$key => $valueIfNotExists]
     *
     * @return array
     */
    private function sortArgumentsByKeyIfExists(array $arrayToSort, array $inOrderKeys)
    {
        $argumentsInOrder = [];

        if ($this->isAssociativeArray($inOrderKeys)) {
            foreach ($inOrderKeys as $key => $valueIfNotExists) {
                $argumentsInOrder[$key] = $arrayToSort[$key] ?? $valueIfNotExists;
            }

            return $argumentsInOrder;
        }

        foreach ($inOrderKeys as $key) {
            if (isset($arrayToSort[$key])) {
                $argumentsInOrder[] = $arrayToSort[$key];
            }
        }

        return $argumentsInOrder;
    }

    private function extractStringValue(string $value): array
    {
        if ('@=' === substr($value, 0, 2)) {
            return ['expr', substr($value, 2)];
        }

        if ('@' === $value[0]) {
            return ['ref', trim($value, '@')];
        }

        if (class_exists($value)) {
            return [null, $value];
        }

        if ('\\' === $value[-1]) {
            $value = str_replace('\\', '\\\\', $value);
        }

        return [null, "'".$value."'"];
    }

    private function addUseStatementIfNecessary(string $className): string
    {
        if (!\in_array($className, $this->useStatements)) {
            $this->useStatements[] = $className;
        }

        return Str::getShortClassName($className).'::class';
    }

    private function addNode($node)
    {
        $this->stmts[] = $node;
    }

    private function addLineStmt(string $line, bool $addBlankLine = null)
    {
        $addBlankLine = $addBlankLine ? "\n" : null;
        $this->addNode(new Node\Name($line.$addBlankLine));
    }

    private function isAlias($serviceKey, $serviceValues): bool
    {
        return isset($serviceValues['alias'])
            || strstr($serviceKey, ' $', true)
            || \is_string($serviceValues) && '@' === $serviceValues[0];
    }

    private function isAssociativeArray(array $array): bool
    {
        return array_keys($array) !== range(0, \count($array) - 1);
    }

    private function indentationIsRequired(array $array): bool
    {
        $nbChars = 0;
        if ($this->isAssociativeArray($array)) {
            foreach ($array as $key => $value) {
                $nbChars += (\strlen($this->toString($key)) + \strlen($this->toString($value)));
            }
        } else {
            foreach ($array as $value) {
                $nbChars += \strlen($this->toString($value));
            }
        }

        return $nbChars > 70;
    }

    /**
     * return a tabulation space.
     */
    private function tab(int $count = 1): string
    {
        return str_repeat(' ', $count * 4);
    }

    private function addEnvironmentImport(string $environment, string $path)
    {
        // if ($kernel->getEnvironment() === 'dev')
        $ifNode = new Node\Stmt\If_(new Node\Expr\BinaryOp\Identical(
            new Node\Expr\MethodCall(
                new Node\Expr\Variable('kernel'),
                'getEnvironment'
            ),
            new Node\Scalar\String_($environment)
        ));

        // $configurator->import('services_dev.php');
        $ifNode->stmts = [
            new Node\Stmt\Expression(new Node\Expr\MethodCall(
                new Node\Expr\Variable('configurator'),
                'import',
                [new Node\Arg(new Node\Scalar\String_($path))]
            )),
        ];

        $this->addNode($ifNode);
    }
}
