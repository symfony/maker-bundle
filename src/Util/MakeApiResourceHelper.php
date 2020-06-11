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

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Doctrine\EntityClassGenerator;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * @author Antoine Michelet <jean.marcel.michelet@gmail.com>
 *
 * @internal
 */
class MakeApiResourceHelper
{
    private $doctrineHelper;
    private $fileManager;
    private $apiFilters = [];
    private $apiFilterStrategies = [];
    private $apiResourceConfiguration = [];
    private $availableApiResourceConfiguration = [
        'collection/item operations',
        'normalization/denormalization groups',
        'pagination',
        'formats',
        'add custom arguments',
        'add custom options',
        'next',
    ];

    public static $availableFilters = [
        'ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter',
        'ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter',
        'ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter',
        'ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter',
        'ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter',
        'ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter',
        'ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter',
        'ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\MatchFilter',
        'ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\TermFilter',
    ];

    public static $availableSearchFilterStrategies = [
        'exact',
        'partial',
        'start',
        'end',
        'word_start',
        'iexact',
        'ipartial',
        'istart',
        'iend',
        'iword_start',
    ];

    public static $availableDateFilterStrategies = [
        'EXCLUDE_NULL',
        'INCLUDE_NULL_BEFORE',
        'INCLUDE_NULL_AFTER',
        'INCLUDE_NULL_BEFORE_AND_AFTER',
    ];

    public static $availableFormats = [
        'application/ld+json' => 'jsonld',
        'n/a' => 'n/a',
        'application/vnd.api+json' => 'jsonapi',
        'application/hal+json' => 'jsonhal',
        'application/x-yaml' => 'yaml',
        'text/csv' => 'csv',
        'text/html' => 'html',
        'application/xml' => 'xml',
        'application/json' => 'json',
    ];

    public const NUMERIC_TYPES = [
        'integer',
        'smallint',
        'bigint',
        'guid',
        'float',
    ];

    public const DATE_TYPES = [
        'datetime',
        'date',
        'time',
    ];

    public function __construct(DoctrineHelper $doctrineHelper, FileManager $fileManager, EntityClassGenerator $entityClassGenerator)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->fileManager = $fileManager;
        $this->entityClassGenerator = $entityClassGenerator;
    }

    public function generateApiResourceConfiguration(ConsoleStyle $io)
    {
        $configured = null;
        while (null === $configured) {
            $question = new ChoiceQuestion(
                'First, configure your api resource <comment>(press enter when you have finished)</comment>',
                $this->availableApiResourceConfiguration,
                'next'
            );
            $choice = $io->askQuestion($question);

            if ('next' === $choice) {
                if (isset($this->apiResourceConfiguration['attributes'])) {
                    $option = "attributes={\n";
                    foreach ($this->apiResourceConfiguration['attributes'] as $key => $value) {
                        $option .= ' *        "'.$key.'"='.$value.",\n";
                    }

                    $option .= ' *     },';
                    $this->apiResourceConfiguration[] = $option;
                    unset($this->apiResourceConfiguration['attributes']);
                }

                return $this->apiResourceConfiguration;
            }

            if ('collection/item operations' === $choice) {
                $this->addApiOperations($io);
                unset($this->availableApiResourceConfiguration[0]);

                continue;
            }

            if ('normalization/denormalization groups' === $choice) {
                $this->addNormalizationConfiguration($io);
                unset($this->availableApiResourceConfiguration[1]);

                continue;
            }

            if ('pagination' === $choice) {
                $this->addPaginationConfiguration($io);
                unset($this->availableApiResourceConfiguration[2]);

                continue;
            }

            if ('formats' === $choice) {
                $this->addFormatsConfiguration($io);
                unset($this->availableApiResourceConfiguration[3]);

                continue;
            }

            if ('add custom arguments' === $choice) {
                $this->addCustomArguments($io);

                continue;
            }

            /*
             * I don't know if with we need to build a questionnaire with a question
             * for the name of option and another questionnaire inside for options.
             * Or just allow the user to write the entire option by himself e.g messenger=true
             */
            if ('add custom options' === $choice) {
                $configured = null;
                while (null === $configured) {
                    $question = new Question('Custom option (e.g. messenger=true or press <return> to stop adding custom options)');
                    $option = $io->askQuestion($question);

                    if (null === $option) {
                        break;
                    }

                    $this->apiResourceConfiguration[] = $option.',';

                    continue;
                }

                continue;
            }
            $io->error(sprintf('Option "%s" is not a valid option.', $choice));

            $configured = true;
        }
    }

    private function addCustomArguments(ConsoleStyle $io)
    {
        if (false === isset($this->apiResourceConfiguration['attributes'])) {
            $this->apiResourceConfiguration['attributes'] = [];
        }
        $attributes = $this->apiResourceConfiguration['attributes'];

        $configured = null;
        while (null === $configured) {
            $question = new Question('Custom argument name (e.g. validation_groups or press <return> to stop adding custom options)');
            $attribute = $io->askQuestion($question);

            if (null === $attribute) {
                $this->apiResourceConfiguration['attributes'] = $attributes;

                break;
            }

            $values = '{';
            while (null === $configured) {
                $question = new Question('Add value to argument (press <return> to stop adding values)');
                $value = $io->askQuestion($question);

                if (null === $value) {
                    $attributes[$attribute] = rtrim($values, ', ').'}';

                    break;
                }
                $value = is_numeric($value) ? $value : '"'.$value.'"';
                $values .= $value.', ';
            }

            continue;
        }
    }

    private function addApiOperations(ConsoleStyle $io)
    {
        $operations = $this->submitQuestionnaire(
            $io,
            'Collection operation (enter ? to see all operations or press <return> to stop adding collection operations)',
            ['get', 'post']
        );

        $strOperations = 'collectionOperations={';

        foreach ($operations as $operation) {
            $strOperations .= sprintf('"%s", ', $operation);
        }

        $strOperations = rtrim($strOperations, ', ');

        $this->apiResourceConfiguration[] = $strOperations.'},';

        $operations = $this->submitQuestionnaire(
            $io,
            'Item operation (enter ? to see all operations or press <return> to stop adding collection operations)',
            ['get', 'put', 'delete', 'patch']
        );

        $strOperations = 'itemOperations={';

        foreach ($operations as $operation) {
            $strOperations .= sprintf('"%s", ', $operation);
        }

        $strOperations = rtrim($strOperations, ', ');

        $this->apiResourceConfiguration[] = $strOperations.'},';
    }

    private function submitQuestionnaire(ConsoleStyle $io, string $questionText, $availables)
    {
        $options = [];
        $configured = null;
        while (null === $configured) {
            if (empty($availables)) {
                break;
            }

            $question = (new Question($questionText))->setAutocompleterValues($availables);
            $choice = $io->askQuestion($question);

            if (null === $choice) {
                break;
            }

            if ('?' === $choice) {
                foreach ($availables as $available) {
                    $io->writeln(sprintf('  * <comment>%s</comment>', $available));
                }

                continue;
            }

            if (false === \in_array($choice, $availables)) {
                $io->error(sprintf('The option "%s" is not available', $choice));

                continue;
            }
            $options[] = $choice;
            $key = array_search($choice, $availables);

            unset($availables[$key]);
        }

        return $options;
    }

    private function addFormatsConfiguration(ConsoleStyle $io)
    {
        $formats = $this->submitQuestionnaire(
            $io,
            'Format (enter ? to see all formats)',
            self::$availableFormats
        );
        $strFormats = "formats={\n";

        foreach ($formats as $key => $format) {
            $strFormats .= sprintf(
                ' *         "%s"={"%s"},'."\n",
                $format,
                array_search($format, self::$availableFormats)
            );
        }

        $this->apiResourceConfiguration[] = $strFormats.' *     },';
    }

    /**
     * Here i test a new paradigm for passing values, I allow the user to pass several groups separated by comas.
     * Tell me if you like.
     */
    private function addNormalizationConfiguration(ConsoleStyle $io)
    {
        $question = new Question(
            'Enter the names of the normalization context groups separated by coma <comment>(e.g. book:read, author:read)</comment>'
        );

        $choices = $io->askQuestion($question);

        $option = $this->asArray($io, 'normalizationContext={"groups"', $choices);
        $option = str_replace('},', '}},', $option);
        $this->apiResourceConfiguration[] = $option;

        $question = new Question(
            'Enter the names of the denormalization context groups separated by coma <comment>(e.g. book:write, author:write)</comment>'
        );

        $choices = $io->askQuestion($question);
        $option = $this->asArray($io, 'denormalizationContext={"groups"', $choices);
        $option = str_replace('},', '}},', $option);
        $this->apiResourceConfiguration[] = $option;
    }

    private function addPaginationConfiguration(ConsoleStyle $io)
    {
        if (false === isset($this->apiResourceConfiguration['attributes'])) {
            $this->apiResourceConfiguration['attributes'] = [];
        }
        $arguments = $this->apiResourceConfiguration['attributes'];

        $availablesOptions = [
            'client_enabled',
            'items_per_page',
            'client_items_per_page',
            'maximum_items_per_page',
            'partial',
            'client_partial',
        ];

        $configured = null;
        while (null === $configured) {
            $question = new Question('Let\'s configuring pagination! (enter <comment>?</comment> to see all types))');

            $question->setAutocompleterValues($availablesOptions);
            $choice = $io->askQuestion($question);

            if (null === $choice) {
                $this->apiResourceConfiguration['attributes'] = $arguments;

                return;
            }

            if ('?' === $choice) {
                foreach ($availablesOptions as $option) {
                    $io->writeln(sprintf('  * <comment>%s</comment>', $option));
                }

                continue;
            }

            if (false === \in_array($choice, $availablesOptions)) {
                $io->error(sprintf('Invalid option "%s".', $choice));

                continue;
            }

            if ('maximum_items_per_page' !== $choice) {
                $choice = 'pagination_'.$choice;
            }

            if ('pagination_client_enabled' === $choice || 'pagination_partial' === $choice || 'pagination_client_partial' === $choice) {
                $question = new Question('Pass true or false', 'true');
                $question->setAutocompleterValues(['true', 'false']);
                $value = $io->askQuestion($question);
                $arguments[$choice] = $value;
            } else {
                $value = $io->ask(sprintf('Quantity %s:', $choice), 30);
                $arguments[$choice] = $value;
            }
            $keyOption = array_search(str_replace('pagination_', '', $choice), $availablesOptions);

            unset($availablesOptions[$keyOption]);
        }
    }

    public function asArray(ConsoleStyle $io, string $optionName, string $subOptions, array $availables = [])
    {
        $subOptions = str_replace(' ', '', $subOptions);
        $subOptions = explode(',', $subOptions);

        $filteredOptions = [];
        foreach ($subOptions as $key => $value) {
            if (isset($filteredOptions[$key]) && $filteredOptions[$key] === $value) {
                continue;
            }

            if (false === empty($availables) && false === \in_array($value, $availables)) {
                $io->note(sprintf('The option "%s" is not available and has been ignored.', $value));

                continue;
            }

            $filteredOptions[$key] = $value;
        }

        $optionName .= '={';
        foreach ($filteredOptions as $key => $value) {
            $value = '"'.$value.'"';
            $optionName .= next($filteredOptions) ? $value.', ' : $value;
        }

        return $optionName .= '},';
    }

    /**
     * For autocompletion, we prefer shortClassName but we need fullClassName for
     * allowing users customizing their own filters.
     */
    public function getApiFilterFullClassNameIfExists($filter): ?string
    {
        if (null === $filter) {
            return null;
        }

        foreach (self::$availableFilters as $fullClassNameFilter) {
            if (strstr($fullClassNameFilter, $filter)) {
                return $fullClassNameFilter;
            }
        }

        return $filter;
    }

    /**
     * @param bool $asShortClassName is for autocompletion case
     */
    public function getFiltersMatchingCurrentType(array $data, bool $asShortClassName = false): array
    {
        $filteredFilters = self::$availableFilters;
        foreach ($filteredFilters as $key => $filter) {
            if (!$this->isTypeCompatibleWithApiFilter($data, $filter) || \in_array($filter, $this->apiFilters)) {
                unset($filteredFilters[$key]);
            } else {
                $filteredFilters[$key] = $asShortClassName ? Str::getShortClassName($filter) : $filter;
            }
        }

        return $filteredFilters;
    }

    public function isTypeCompatibleWithApiFilter(array $data, string $apiFilter): bool
    {
        $type = $data['type'];
        switch (Str::getShortClassName($apiFilter)) {
            case 'SearchFilter':
                return \in_array($type, self::NUMERIC_TYPES) || 'string' === $type || 'text' === $type;
            case 'DateFilter':
                return \in_array($type, self::DATE_TYPES);
            case 'BooleanFilter':
                return 'boolean' === $type;
            case 'NumericFilter':
                return \in_array($type, self::NUMERIC_TYPES);
            case 'RangeFilter':
                return \in_array($type, self::NUMERIC_TYPES);
            case 'ExistsFilter':
                return isset($data['nullable']);
            case 'OrderFilter':
                return \in_array($type, self::NUMERIC_TYPES) || 'string' === $type || 'text' === $type;
            case 'MatchFilter':
                return 'string' === $type || 'text' === $type;
            case 'TermFilter':
                return \in_array($type, self::NUMERIC_TYPES) || 'string' === $type || 'text' === $type;
            default:
                return false;
        }
    }
}
