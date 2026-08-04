<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Test\MakerTestKernel;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\ProfileCatalog;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Reason;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\UsesProfile;

/**
 * The non-circular dependency-declaration contract between makers and the
 * fixed app profiles of the functional suite. Fixed profiles must never become
 * their own source of truth:
 *
 *  - every package a maker declares via configureDependencies() must be a
 *    direct requirement of every profile its functional tests run against
 *    (so a maker cannot silently rely on an undeclared package), and
 *  - every profile requirement tagged declared-by-maker must actually be
 *    declared by at least one maker tested on that profile (or a descendant),
 *    so profiles cannot accumulate unjustified packages.
 *
 * Honest limitation: this proves "declared => installed", not "used =>
 * declared". The mega profile is rich enough that a maker could still lean on
 * an undeclared package it happens to find there; the InstalledVersions lean
 * guards cover the historically bug-prone cases (twig, live-component,
 * mercure, php-cs-fixer), not every combination.
 *
 * Booting the full maker locator loads the deprecated makers too, which
 * legitimately triggers their deprecations — hence the legacy group.
 *
 * @group legacy
 */
#[Group('legacy')]
class DependencyContractTest extends TestCase
{
    /**
     * Composer packages the flex aliases used in configureDependencies()
     * resolve to. Aliases resolving to packs list every relevant pack member.
     */
    private const ALIAS_MAP = [
        'console' => ['symfony/console'],
        'yaml' => ['symfony/yaml'],
        'validator' => ['symfony/validator'],
        'serializer' => ['symfony/serializer', 'symfony/property-access'],
        'messenger' => ['symfony/messenger'],
        'orm' => ['symfony/orm-pack'],
        'doctrine' => ['symfony/orm-pack'],
        'orm-fixtures' => ['doctrine/doctrine-fixtures-bundle'],
        'security' => ['symfony/security-bundle'],
        'security-bundle' => ['symfony/security-bundle'],
        'security-csrf' => ['symfony/security-csrf'],
        'form' => ['symfony/form'],
        'router' => ['symfony/routing'],
        'twig' => ['symfony/twig-bundle'],
        'twig-bundle' => ['symfony/twig-bundle'],
        'browser-kit' => ['symfony/browser-kit'],
        'css-selector' => ['symfony/css-selector'],
        'panther' => ['symfony/panther'],
    ];

    /**
     * Shipped by symfony/skeleton itself: never a profile concern.
     */
    private const SKELETON_PROVIDED = [
        'symfony/console',
        'symfony/dotenv',
        'symfony/flex',
        'symfony/framework-bundle',
        'symfony/runtime',
        'symfony/yaml',
        // not a direct skeleton requirement, but framework-bundle hard-requires
        // it; every app has it, so the "router" alias is always satisfied
        'symfony/routing',
    ];

    /**
     * Packages makers install at runtime through DependencyInstaller. They are
     * preinstalled in profiles (zero network at test time), tagged
     * runtime-installer, and their installer behavior is unit-tested in
     * Tests\Util\DependencyInstallerTest.
     */
    private const KNOWN_RUNTIME_INSTALLED = [
        'symfony/scheduler',
        'symfony/webhook',
        // MakeWebhook installs it when an ExpressionRequestMatcher is chosen
        'symfony/expression-language',
        'symfony/security-bundle',
        'symfony/ux-turbo',
    ];

    public function testEveryMakerDeclarationIsSatisfiedByItsProfiles()
    {
        $kernel = new MakerTestKernel('dev', true);
        $kernel->boot();
        $locator = $kernel->getContainer()->get('maker_locator_for_tests');

        foreach ($this->makerToProfilesFromFunctionalTests() as $makerClass => $profiles) {
            $dependencies = new DependencyBuilder();
            $locator->get($makerClass)->configureDependencies($dependencies);

            $declared = [
                ...$dependencies->getAllRequiredDependencies(),
                ...$dependencies->getAllRequiredDevDependencies(),
            ];

            foreach ($profiles as $profileName) {
                $available = [...self::SKELETON_PROVIDED, ...$this->directRequirementsOfChain($profileName)];

                foreach ($declared as $declaration) {
                    foreach (self::ALIAS_MAP[$declaration] ?? [$declaration] as $package) {
                        $this->assertContains($package, $available, \sprintf(
                            '%s declares "%s" (package "%s") but the "%s" profile chain does not install it. Add it to the profile with reason "%s" — a maker must never silently rely on an undeclared package.',
                            $makerClass,
                            $declaration,
                            $package,
                            $profileName,
                            Reason::DECLARED_BY_MAKER,
                        ));
                    }
                }
            }
        }
    }

    public function testEveryDeclaredByMakerRequirementHasADeclaringMaker()
    {
        $kernel = new MakerTestKernel('dev', true);
        $kernel->boot();
        $locator = $kernel->getContainer()->get('maker_locator_for_tests');

        $declaredPackagesByProfile = [];
        foreach ($this->makerToProfilesFromFunctionalTests() as $makerClass => $profiles) {
            $dependencies = new DependencyBuilder();
            $locator->get($makerClass)->configureDependencies($dependencies);

            $packages = [];
            foreach ([...$dependencies->getAllRequiredDependencies(), ...$dependencies->getAllRequiredDevDependencies()] as $declaration) {
                $packages = [...$packages, ...(self::ALIAS_MAP[$declaration] ?? [$declaration])];
            }

            foreach ($profiles as $profileName) {
                $declaredPackagesByProfile[$profileName] = [...$declaredPackagesByProfile[$profileName] ?? [], ...$packages];
            }
        }

        foreach (ProfileCatalog::all() as $profile) {
            $declaredInProfileOrDescendants = [];
            foreach (array_keys($declaredPackagesByProfile) as $profileName) {
                if ($this->chainContains($profileName, $profile->name)) {
                    $declaredInProfileOrDescendants = [...$declaredInProfileOrDescendants, ...$declaredPackagesByProfile[$profileName]];
                }
            }

            foreach ([...$profile->require, ...$profile->requireDev] as $package => $config) {
                if (Reason::DECLARED_BY_MAKER === $config['reason']) {
                    // profiles not yet used by any migrated test cannot be checked
                    if (!$declaredInProfileOrDescendants && !isset($declaredPackagesByProfile[$profile->name])) {
                        continue;
                    }

                    $this->assertContains($package, $declaredInProfileOrDescendants, \sprintf(
                        'Profile "%s" requires "%s" with reason "declared-by-maker", but no maker tested on it (or a descendant profile) declares that package. Fix the reason or drop the package — over-installation masks missing-dependency bugs.',
                        $profile->name,
                        $package,
                    ));
                }

                if (Reason::RUNTIME_INSTALLER === $config['reason']) {
                    $this->assertContains($package, self::KNOWN_RUNTIME_INSTALLED, \sprintf(
                        'Profile "%s" tags "%s" as runtime-installer, but no maker is known to auto-install it.',
                        $profile->name,
                        $package,
                    ));
                }
            }
        }
    }

    /**
     * @return array<class-string, list<string>> maker class => profiles its functional tests run against
     */
    private function makerToProfilesFromFunctionalTests(): array
    {
        $map = [];

        foreach (glob(__DIR__.'/Functional/*Test.php') as $file) {
            $class = 'Symfony\\Bundle\\MakerBundle\\Tests\\Functional\\'.basename($file, '.php');
            $reflection = new \ReflectionClass($class);

            /** @var MakerTest|null $makerTest */
            $makerTest = ($reflection->getAttributes(MakerTest::class)[0] ?? null)?->newInstance();
            if (null === $makerTest) {
                continue;
            }

            $profiles = [$makerTest->profile];
            foreach ($reflection->getMethods() as $method) {
                if ($usesProfile = ($method->getAttributes(UsesProfile::class)[0] ?? null)?->newInstance()) {
                    $profiles[] = $usesProfile->profile;
                }
            }

            $map[$makerTest->maker] = array_values(array_unique([...$map[$makerTest->maker] ?? [], ...$profiles]));
        }

        $this->assertNotEmpty($map);

        return $map;
    }

    /**
     * @return list<string> direct requirements of the profile and its ancestors
     */
    private function directRequirementsOfChain(string $profileName): array
    {
        $packages = [];
        for ($profile = ProfileCatalog::get($profileName); null !== $profile; $profile = $profile->extends ? ProfileCatalog::get($profile->extends) : null) {
            $packages = [...$packages, ...array_keys($profile->require), ...array_keys($profile->requireDev)];
        }

        return $packages;
    }

    private function chainContains(string $profileName, string $ancestorName): bool
    {
        for ($profile = ProfileCatalog::get($profileName); null !== $profile; $profile = $profile->extends ? ProfileCatalog::get($profile->extends) : null) {
            if ($profile->name === $ancestorName) {
                return true;
            }
        }

        return false;
    }
}
