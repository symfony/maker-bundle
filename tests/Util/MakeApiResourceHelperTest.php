<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Doctrine\EntityClassGenerator;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Util\MakeApiResourceHelper;

/**
 * Class test only created for testing the method isTypeCompatibleWithApiFilter()
 * because it's too complex to functionally test. Other cases are well covered.
 */
class MakeApiResourceHelperTest extends TestCase
{
    public function testTypeCompatibilityWithSearchFilter()
    {
        $entityHelper = $this->getMakeApiResourceHelper();

        $matchingTypes = $entityHelper::NUMERIC_TYPES;
        array_push($matchingTypes, 'string', 'text');

        $this->verifyOrmFilterCompatibility($entityHelper, $matchingTypes, 'SearchFilter');

        $dismatchingTypes = $entityHelper::DATE_TYPES + ['boolean'];

        $this->verifyOrmFilterCompatibility($entityHelper, $dismatchingTypes, 'SearchFilter', false);
    }

    public function testTypeCompatibilityWithDateFilter()
    {
        $entityHelper = $this->getMakeApiResourceHelper();

        $this->verifyOrmFilterCompatibility($entityHelper, $entityHelper::DATE_TYPES, 'DateFilter');

        $dismatchingTypes = $entityHelper::NUMERIC_TYPES + ['string', 'text', 'boolean'];

        $this->verifyOrmFilterCompatibility($entityHelper, $dismatchingTypes, 'DateFilter', false);
    }

    public function testTypeCompatibilityWithBooleanFilter()
    {
        $entityHelper = $this->getMakeApiResourceHelper();

        $this->verifyOrmFilterCompatibility($entityHelper, ['boolean'], 'BooleanFilter');

        $dismatchingTypes = $entityHelper::NUMERIC_TYPES + $entityHelper::DATE_TYPES + ['string', 'text'];

        $this->verifyOrmFilterCompatibility($entityHelper, $dismatchingTypes, 'BooleanFilter', false);
    }

    public function testTypeCompatibilityWithNumericAndRangeFilters()
    {
        $entityHelper = $this->getMakeApiResourceHelper();

        $this->verifyOrmFilterCompatibility($entityHelper, $entityHelper::NUMERIC_TYPES, 'NumericFilter');
        $this->verifyOrmFilterCompatibility($entityHelper, $entityHelper::NUMERIC_TYPES, 'RangeFilter');

        $dismatchingTypes = $entityHelper::DATE_TYPES + ['string', 'text', 'boolean'];

        $this->verifyOrmFilterCompatibility($entityHelper, $dismatchingTypes, 'NumericFilter', false);
        $this->verifyOrmFilterCompatibility($entityHelper, $dismatchingTypes, 'RangeFilter', false);
    }

    public function testTypeCompatibilityWithExistsFilter()
    {
        $entityHelper = $this->getMakeApiResourceHelper();

        $this->assertTrue($entityHelper->isTypeCompatibleWithApiFilter(
            ['type' => 'string', 'nullable' => true],
            'ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter'
        ));

        $this->assertFalse($entityHelper->isTypeCompatibleWithApiFilter(
            ['type' => 'string'],
            'ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter'
        ));
    }

    public function testTypeCompatibilityWithMatchFilter()
    {
        $entityHelper = $this->getMakeApiResourceHelper();

        $this->verifyElasticSearchFilterCompatibility($entityHelper, ['string', 'text'], 'MatchFilter');

        $dismatchingTypes = $entityHelper::DATE_TYPES + $entityHelper::NUMERIC_TYPES + ['boolean'];

        $this->verifyElasticSearchFilterCompatibility($entityHelper, $dismatchingTypes, 'MatchFilter', false);
    }

    public function testTypeCompatibilityWithTermFilter()
    {
        $entityHelper = $this->getMakeApiResourceHelper();

        $matchingTypes = $entityHelper::NUMERIC_TYPES + ['string', 'text'];

        $this->verifyElasticSearchFilterCompatibility($entityHelper, $matchingTypes, 'TermFilter');

        $dismatchingTypes = $entityHelper::DATE_TYPES + ['boolean'];

        $this->verifyElasticSearchFilterCompatibility($entityHelper, $dismatchingTypes, 'TermFilter', false);
    }

    private function verifyOrmFilterCompatibility(MakeApiResourceHelper $entityHelper, array $matchingTypes, string $filter, bool $shouldMatch = true)
    {
        $filter = 'ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\\'.$filter;

        $this->verifyFilterCompatibility($entityHelper, $matchingTypes, $filter, $shouldMatch);
    }

    private function verifyElasticSearchFilterCompatibility(MakeApiResourceHelper $entityHelper, array $matchingTypes, string $filter, bool $shouldMatch = true)
    {
        $filter = 'ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\\'.$filter;

        $this->verifyFilterCompatibility($entityHelper, $matchingTypes, $filter, $shouldMatch);
    }

    private function verifyFilterCompatibility(MakeApiResourceHelper $entityHelper, array $matchingTypes, string $filter, bool $shouldMatch)
    {
        foreach ($matchingTypes as $type) {
            $isCompatible = $entityHelper->isTypeCompatibleWithApiFilter(
                ['type' => $type],
                $filter
            );

            if (true === $shouldMatch) {
                $this->assertTrue($isCompatible);
            } else {
                $this->assertFalse($isCompatible);
            }
        }
    }

    private function getMakeApiResourceHelper()
    {
        $doctrineHelper = new DoctrineHelper('Namespace');
        $fileManager = $this->createMock(FileManager::class);
        $entityClassManager = new EntityClassGenerator(new Generator($fileManager, 'Namespace'), $doctrineHelper);

        return new MakeApiResourceHelper($doctrineHelper, $fileManager, $entityClassManager);
    }
}
