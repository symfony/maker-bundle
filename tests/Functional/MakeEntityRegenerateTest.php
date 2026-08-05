<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Functional;

use Symfony\Bundle\MakerBundle\Maker\MakeEntity;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\ExecutionProof;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\SkipOnPostgres;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\WindowsSmoke;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeEntity::class, profile: Profiles::MEGA)]
final class MakeEntityRegenerateTest extends MakerTestCase
{
    #[LegacyCase('MakeEntityTest::it_regenerates_entities')]
    #[SkipOnPostgres('the fixture entities map "user", a reserved table name there')]
    #[WindowsSmoke]
    public function testItRegeneratesEntities()
    {
        $this->app->prepareDatabase();
        $this->app->copyFixture('regenerate/attributes', '');

        $this->app->runMaker()
            ->arguments('--regenerate')
            ->acceptDefault('Enter a class or namespace to regenerate')
            ->run();

        $this->runCustomTest('it_regenerates_entities.php', covers: $this->updatedEntityPaths());
    }

    #[LegacyCase('MakeEntityTest::it_regenerates_embedded_entities')]
    public function testItRegeneratesEmbeddedEntities()
    {
        $this->app->prepareDatabase();
        $this->app->copyFixture('regenerate-embedded/attributes', '');

        $this->app->runMaker()
            ->arguments('--regenerate')
            ->acceptDefault('Enter a class or namespace to regenerate')
            ->run();

        $this->runCustomTest('it_regenerates_embedded_entities.php', covers: $this->updatedEntityPaths());
    }

    #[LegacyCase('MakeEntityTest::it_regenerates_embeddable_entity')]
    public function testItRegeneratesEmbeddableEntity()
    {
        $this->app->prepareDatabase();
        $this->app->copyFixture('regenerate-embeddable/attributes', '');

        $this->app->runMaker()
            ->arguments('--regenerate')
            ->acceptDefault('Enter a class or namespace to regenerate')
            ->run();

        $this->runCustomTest('it_regenerates_embeddable_entity.php', covers: $this->updatedEntityPaths());
    }

    #[LegacyCase('MakeEntityTest::it_regenerates_with_overwrite')]
    public function testItRegeneratesWithOverwrite()
    {
        $this->copyEntityFixture('User-invalid-method.php');

        $this->app->runMaker()
            ->arguments('--regenerate --overwrite')
            ->acceptDefault('Enter a class or namespace to regenerate')
            ->run();

        $this->app->runGeneratedTests('it_regenerates_with_overwrite.php', covers: $this->updatedEntityPaths());
    }

    #[LegacyCase('MakeEntityTest::it_can_overwrite_while_adding_fields')]
    public function testItCanOverwriteWhileAddingFields()
    {
        $this->app->prepareDatabase();
        $this->copyEntityFixture('User-invalid-method-no-property.php');

        $this->app->runMaker()
            ->arguments('--overwrite')
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('New property name', 'firstName')
            ->answer('Field type', 'string')
            ->acceptDefault('Field length')
            ->acceptDefault('nullable')
            ->acceptDefault('Add another property')
            ->run();

        $this->app->updateSchema();
        $this->app->runGeneratedTests('it_regenerates_with_overwrite.php', covers: $this->updatedEntityPaths());
    }

    /**
     * --regenerate touches whatever entities the copied setup contained, so
     * covers derives from the artifacts the maker actually reported.
     *
     * @return list<string>
     */
    private function updatedEntityPaths(): array
    {
        $paths = [];
        foreach ($this->app->proof()->requiredOfType(ExecutionProof::TYPE_PHP) as $path) {
            if (str_starts_with($path, 'src/Entity/')) {
                $paths[] = $path;
            }
        }

        self::assertNotEmpty($paths);

        return $paths;
    }

    private function copyEntityFixture(string $filename): void
    {
        $entityClassName = substr($filename, 0, strpos($filename, '-'));
        $this->app->copyFixture(
            'entities/attributes/'.$filename,
            \sprintf('src/Entity/%s.php', $entityClassName),
        );
    }

    /**
     * @param list<string> $covers
     */
    private function runCustomTest(string $fixtureTestFile, array $covers): void
    {
        $this->app->updateSchema();
        $this->app->runGeneratedTests($fixtureTestFile, covers: $covers);
    }
}
