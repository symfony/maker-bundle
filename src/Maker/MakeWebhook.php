<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputAwareMakerInterface;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\AttributesRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\ExpressionRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\HostRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\IpsRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\PortRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\SchemeRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Maelan LE BORGNE <maelan.leborgne@gmail.com>
 *
 * @internal
 */
final class MakeWebhook extends AbstractMaker implements InputAwareMakerInterface
{
    /** @see https://regex101.com/r/S3BWkx/1 */
    public const WEBHOOK_NAME_PATTERN = '/^[a-zA-Z_.\-\x80-\xff][a-zA-Z0-9_.\-\x80-\xff]*$/u';
    private const WEBHOOK_CONFIG_PATH = 'config/packages/webhook.yaml';
    private YamlSourceManipulator $ysm;
    private ?string $name;

    public function __construct(
        private FileManager $fileManager,
        private Generator $generator,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:webhook';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a new Webhook';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of the webhook to create (e.g. <fg=yellow>github, stripe, ...</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeWebhook.txt'))
        ;

        $inputConfig->setArgumentAsNonInteractive('name');
    }

    public function configureDependencies(DependencyBuilder $dependencies, ?InputInterface $input = null): void
    {
        $dependencies->addClassDependency(
            AbstractRequestParser::class,
            'webhook'
        );
        $dependencies->addClassDependency(
            ConsumerInterface::class,
            'remote-event'
        );
        $dependencies->addClassDependency(
            Yaml::class,
            'yaml'
        );
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if ($this->name = $input->getArgument('name')) {
            if (!$this->verifyWebhookName($this->name)) {
                throw new RuntimeCommandException('A webhook name can only have alphanumeric characters, underscores, dots, and dashes.');
            }

            return;
        }

        $argument = $command->getDefinition()->getArgument('name');
        $question = new Question($argument->getDescription());
        $question->setValidator(Validator::notBlank(...));

        $this->name = $io->askQuestion($question);
        while (!$this->verifyWebhookName($this->name)) {
            $io->error('A webhook name can only have alphanumeric characters, underscores, dots, and dashes.');
            $this->name = $io->askQuestion($question);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $requestParserDetails = $this->generator->createClassNameDetails(
            Str::asClassName($this->name.'RequestParser'),
            'Webhook\\'
        );
        $remoteEventHandlerDetails = $this->generator->createClassNameDetails(
            Str::asClassName($this->name.'WebhookHandler'),
            'RemoteEvent\\'
        );

        $this->addToYamlConfig($this->name, $requestParserDetails);

        $this->generateRequestParser($io, $requestParserDetails);

        $this->generator->generateClass(
            $remoteEventHandlerDetails->getFullName(),
            'webhook/WebhookHandler.tpl.php',
            [
                'webhook_name' => $this->name,
            ]
        );

        $this->generator->writeChanges();
        $this->fileManager->dumpFile(self::WEBHOOK_CONFIG_PATH, $this->ysm->getContents());
    }

    private function verifyWebhookName(string $entityName): bool
    {
        return preg_match(self::WEBHOOK_NAME_PATTERN, $entityName);
    }

    private function addToYamlConfig(string $webhookName, ClassNameDetails $requestParserDetails): void
    {
        $yamlConfig = Yaml::dump(['framework' => ['webhook' => ['routing' => []]]], 4, 2);
        if ($this->fileManager->fileExists(self::WEBHOOK_CONFIG_PATH)) {
            $yamlConfig = $this->fileManager->getFileContents(self::WEBHOOK_CONFIG_PATH);
        }

        $this->ysm = new YamlSourceManipulator($yamlConfig);
        $arrayConfig = $this->ysm->getData();

        if (\array_key_exists($webhookName, $arrayConfig['framework']['webhook']['routing'] ?? [])) {
            throw new \InvalidArgumentException('A webhook with this name already exists');
        }

        $arrayConfig['framework']['webhook']['routing'][$webhookName] = [
            'service' => $requestParserDetails->getFullName(),
            'secret' => 'your_secret_here',
        ];
        $this->ysm->setData(
            $arrayConfig
        );
    }

    /**
     * @throws \Exception
     */
    private function generateRequestParser(ConsoleStyle $io, ClassNameDetails $requestParserDetails): void
    {
        $useStatements = new UseStatementGenerator([
            JsonException::class,
            Request::class,
            Response::class,
            RemoteEvent::class,
            AbstractRequestParser::class,
            RejectWebhookException::class,
            RequestMatcherInterface::class,
        ]);

        $requestMatchers = [];
        while (true) {
            $newRequestMatcher = $this->askForNextRequestMatcher($io, $requestMatchers, $requestParserDetails->getFullName(), empty($requestMatchers));
            if (null === $newRequestMatcher) {
                break;
            }
            $requestMatchers[] = $newRequestMatcher;
        }

        // Use a ChainRequestMatcher if multiple matchers have been added OR if none (will be printed with an empty array)
        $useChainRequestsMatcher = false;
        if (1 !== \count($requestMatchers)) {
            $useChainRequestsMatcher = true;
            $useStatements->addUseStatement(ChainRequestMatcher::class);
        }

        $requestMatcherArguments = [];
        foreach ($requestMatchers as $requestMatcherClass) {
            $useStatements->addUseStatement($requestMatcherClass);
            $requestMatcherArguments[$requestMatcherClass] = $this->getRequestMatcherArguments($requestMatcherClass);
            if (ExpressionRequestMatcher::class === $requestMatcherClass) {
                if (!class_exists(Expression::class)) {
                    throw new \Exception('The ExpressionRequestMatcher requires the symfony/expression-language package.');
                }
                $useStatements->addUseStatement(Expression::class);
                $useStatements->addUseStatement(ExpressionLanguage::class);
            }
        }

        $this->generator->generateClass(
            $requestParserDetails->getFullName(),
            'webhook/RequestParser.tpl.php',
            [
                'use_statements' => $useStatements,
                'use_chained_requests_matcher' => $useChainRequestsMatcher,
                'request_matchers' => $requestMatchers,
                'request_matcher_arguments' => $requestMatcherArguments,
            ]
        );
    }

    private function askForNextRequestMatcher(ConsoleStyle $io, array $addedMatchers, string $entityClass, bool $isFirstMatcher): ?string
    {
        $io->writeln('');
        $availableMatchers = $this->getAvailableRequestMatchers();
        $matcherName = null;
        while (null === $matcherName) {
            if ($isFirstMatcher) {
                $questionText = 'Add a RequestMatcher (press <return> to skip this step)';
            } else {
                $questionText = 'Add another RequestMatcher? Enter the RequestMatcher name (or press <return> to stop adding matchers)';
            }

            $choices = array_diff($availableMatchers, $addedMatchers);
            $question = new ChoiceQuestion($questionText, array_values(['<skip>'] + $choices), 0);
            $matcherName = $io->askQuestion($question);

            if ('<skip>' === $matcherName) {
                return null;
            }
        }

        return $matcherName;
    }

    private function getAvailableRequestMatchers(): array
    {
        return [
            AttributesRequestMatcher::class,
            ExpressionRequestMatcher::class,
            HostRequestMatcher::class,
            IpsRequestMatcher::class,
            IsJsonRequestMatcher::class,
            MethodRequestMatcher::class,
            PathRequestMatcher::class,
            PortRequestMatcher::class,
            SchemeRequestMatcher::class,
        ];
    }

    private function getRequestMatcherArguments(string $requestMatcherClass)
    {
        return match ($requestMatcherClass) {
            AttributesRequestMatcher::class => '[\'attributeName\' => \'regex\']',
            ExpressionRequestMatcher::class => 'new ExpressionLanguage(), new Expression(\'expression\')',
            HostRequestMatcher::class, PathRequestMatcher::class => '\'regex\'',
            IpsRequestMatcher::class => '[\'127.0.0.1\']',
            IsJsonRequestMatcher::class => '',
            MethodRequestMatcher::class => '\'POST\'',
            PortRequestMatcher::class => '443',
            SchemeRequestMatcher::class => 'https',
            default => '[]',
        };
    }
}
