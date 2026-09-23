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

use Symfony\Bundle\MakerBundle\Ci\CiPlatform;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Sébastien Jean <sebastien.jean76@gmail.com>
 */
final class MakeCi extends AbstractMaker
{
    private const DATABASES = [
        'postgres' => 'PostgreSQL',
        'mysql' => 'MySQL',
        'mariadb' => 'MariaDB',
        'sqlite' => 'SQLite',
        'none' => 'None',
    ];

    /** @var array<string, mixed>|null */
    private ?array $composerJson = null;

    public function __construct(private readonly FileManager $fileManager)
    {
    }

    public static function getCommandName(): string
    {
        return 'make:ci';
    }

    public static function getCommandDescription(): string
    {
        return 'Generate CI/CD configuration for your Symfony application';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addOption('platform', null, InputOption::VALUE_REQUIRED, 'CI platform to use: github-actions or gitlab-ci')
            ->addOption('php-version', null, InputOption::VALUE_REQUIRED, 'PHP version used in production, e.g. 8.4 (default: detected from composer.json)')
            ->addOption('branch', null, InputOption::VALUE_REQUIRED, 'Name of the default branch (default: detected from .git, or "main")')
            ->addOption('database', null, InputOption::VALUE_REQUIRED, 'Database engine: '.implode(', ', array_keys(self::DATABASES)).' (default: detected from DATABASE_URL in .env)')
            ->setHelp($this->getHelpFileContents('MakeCi.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (!$input->getOption('platform')) {
            $platform = CiPlatform::from($io->choice(
                'Which CI platform do you want to generate configuration for?',
                CiPlatform::labels(),
                CiPlatform::GitHubActions->value,
            ));
            $input->setOption('platform', match ($platform) {
                CiPlatform::GitHubActions => 'github-actions',
                CiPlatform::GitLabCi => 'gitlab-ci',
            });
        }

        if (!$input->getOption('php-version')) {
            $input->setOption('php-version', $io->ask(
                'Which PHP version does your application use in production?',
                $this->detectPhpVersion(),
            ));
        }

        if (!$input->getOption('branch')) {
            $input->setOption('branch', $io->ask(
                'What is the name of your default branch?',
                $this->detectDefaultBranch(),
            ));
        }

        if ($this->hasDoctrine() && !$input->getOption('database')) {
            $input->setOption('database', $io->choice(
                'Which database engine does your application use?',
                self::DATABASES,
                $this->detectDatabaseType(),
            ));
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        if (!$input->getOption('platform')) {
            throw new RuntimeCommandException('The CI platform is missing: pass the --platform option with "github-actions" or "gitlab-ci".');
        }

        $platform = self::resolvePlatformOption($input->getOption('platform'));
        $profile = $this->buildProfile($input);

        $io->section('Detected project configuration');
        $this->displayProfile($io, $profile, $platform);

        match ($platform) {
            CiPlatform::GitHubActions => $this->generateGitHubActions($generator, $profile),
            CiPlatform::GitLabCi => $this->generateGitLabCi($generator, $profile),
        };

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        match ($platform) {
            CiPlatform::GitHubActions => $io->text([
                'Next: review the generated files in <fg=yellow>.github/workflows/</>',
                'Find the documentation at <fg=yellow>https://docs.github.com/en/actions</>',
            ]),
            CiPlatform::GitLabCi => $io->text([
                'Next: review the generated <fg=yellow>.gitlab-ci.yml</> file',
                'Find the documentation at <fg=yellow>https://docs.gitlab.com/ci/</>',
            ]),
        };
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProfile(InputInterface $input): array
    {
        $allDeps = self::getAllDependencies($this->getComposerJson());
        $hasDoctrine = $this->hasDoctrine();

        $phpVersion = $input->getOption('php-version') ?: $this->detectPhpVersion();
        if (!preg_match('/^\d+\.\d+$/', $phpVersion)) {
            throw new RuntimeCommandException(\sprintf('Invalid PHP version "%s" for the --php-version option. Use a "major.minor" version, e.g. "8.4".', $phpVersion));
        }

        $databaseType = $hasDoctrine
            ? self::resolveDatabaseOption($input->getOption('database') ?: $this->detectDatabaseType())
            : null;

        return [
            'php_version' => $phpVersion,
            'default_branch' => $input->getOption('branch') ?: $this->detectDefaultBranch(),
            'has_phpunit' => isset($allDeps['phpunit/phpunit']) || isset($allDeps['symfony/test-pack']),
            'has_php_cs_fixer' => isset($allDeps['friendsofphp/php-cs-fixer']),
            'has_phpstan' => isset($allDeps['phpstan/phpstan']),
            'has_twig_cs_fixer' => isset($allDeps['vincentlanglet/twig-cs-fixer']),
            'has_doctrine' => $hasDoctrine,
            'has_doctrine_migrations' => isset($allDeps['doctrine/doctrine-migrations-bundle']) || isset($allDeps['symfony/orm-pack']),
            'has_twig' => isset($allDeps['symfony/twig-bundle']),
            'has_translation' => isset($allDeps['symfony/translation']),
            'has_xliff_translations' => $this->hasTranslationFiles(['xlf', 'xliff']),
            'has_yaml_translations' => $this->hasTranslationFiles(['yaml', 'yml']),
            'has_asset_mapper' => isset($allDeps['symfony/asset-mapper']),
            'database_type' => $databaseType,
        ];
    }

    private function hasDoctrine(): bool
    {
        $allDeps = self::getAllDependencies($this->getComposerJson());

        // symfony/orm-pack stays as is in composer.json when it was not unpacked
        return isset($allDeps['doctrine/orm']) || isset($allDeps['symfony/orm-pack']);
    }

    /** @return array<string, mixed> */
    private function getComposerJson(): array
    {
        return $this->composerJson ??= json_decode(
            $this->fileManager->getFileContents('composer.json'),
            true,
            512,
            \JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @param array<string, mixed> $composerJson
     *
     * @return array<string, string>
     */
    private static function getAllDependencies(array $composerJson): array
    {
        return array_merge(
            $composerJson['require'] ?? [],
            $composerJson['require-dev'] ?? [],
        );
    }

    /**
     * @param list<string> $extensions
     */
    private function hasTranslationFiles(array $extensions): bool
    {
        $rootDir = $this->fileManager->getRootDirectory();
        $translationsDir = $rootDir.'/translations';

        if (!is_dir($translationsDir)) {
            return false;
        }

        foreach ($extensions as $ext) {
            if (glob($translationsDir.'/*.'.$ext)) {
                return true;
            }
        }

        return false;
    }

    private function detectPhpVersion(): string
    {
        $constraint = $this->getComposerJson()['require']['php'] ?? '>=8.2';

        if (preg_match('/(\d+\.\d+)/', $constraint, $matches)) {
            return $matches[1];
        }

        return '8.2';
    }

    private function detectDefaultBranch(): string
    {
        $rootDir = $this->fileManager->getRootDirectory();
        $headRef = $rootDir.'/.git/refs/remotes/origin/HEAD';

        if (file_exists($headRef)) {
            $content = file_get_contents($headRef);

            if (preg_match('#ref: refs/remotes/origin/(.+)$#m', $content, $matches)) {
                return trim($matches[1]);
            }
        }

        return 'main';
    }

    private function detectDatabaseType(): string
    {
        if (!$this->fileManager->fileExists('.env')) {
            return 'postgres';
        }

        $envContent = $this->fileManager->getFileContents('.env');

        if (!preg_match('/^DATABASE_URL=["\']?(.+)$/m', $envContent, $matches)) {
            return 'postgres';
        }

        $url = trim($matches[1], "\"' ");

        // Also match the DBAL scheme aliases, e.g. "pdo-mysql://" or "sqlite3://"
        if (preg_match('#^(pdo[-_])?(postgresql|postgres|pgsql)://#', $url)) {
            return 'postgres';
        }

        if (preg_match('#^(pdo[-_])?(mysql|mysql2|mysqli)://#', $url)) {
            return false !== stripos($url, 'mariadb') ? 'mariadb' : 'mysql';
        }

        if (preg_match('#^(pdo[-_])?(sqlite|sqlite3)://#', $url)) {
            return 'sqlite';
        }

        return 'postgres';
    }

    private static function resolvePlatformOption(string $value): CiPlatform
    {
        return match ($value) {
            'github-actions', 'github' => CiPlatform::GitHubActions,
            'gitlab-ci', 'gitlab' => CiPlatform::GitLabCi,
            default => throw new RuntimeCommandException(\sprintf('Unknown platform "%s". Use "github-actions" or "gitlab-ci".', $value)),
        };
    }

    private static function resolveDatabaseOption(string $value): ?string
    {
        if (!isset(self::DATABASES[$value])) {
            throw new RuntimeCommandException(\sprintf('Unknown database "%s" for the --database option. Use one of: "%s".', $value, implode('", "', array_keys(self::DATABASES))));
        }

        return 'none' === $value ? null : $value;
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function displayProfile(ConsoleStyle $io, array $profile, CiPlatform $platform): void
    {
        $io->text(\sprintf('PHP version: <info>%s</info>', $profile['php_version']));
        $io->text(\sprintf('Default branch: <info>%s</info>', $profile['default_branch']));
        $io->text(\sprintf('Platform: <info>%s</info>', $platform->value));

        $tools = array_filter([
            $profile['has_phpunit'] ? 'PHPUnit' : null,
            $profile['has_php_cs_fixer'] ? 'PHP CS Fixer' : null,
            $profile['has_phpstan'] ? 'PHPStan' : null,
            $profile['has_twig_cs_fixer'] ? 'Twig CS Fixer' : null,
            $profile['has_doctrine'] ? \sprintf('Doctrine ORM (%s)', match ($profile['database_type']) {
                'postgres' => 'PostgreSQL',
                'mysql' => 'MySQL',
                'mariadb' => 'MariaDB',
                'sqlite' => 'SQLite',
                default => 'no database service',
            }) : null,
            $profile['has_twig'] ? 'Twig' : null,
            $profile['has_translation'] ? 'Translation' : null,
            $profile['has_asset_mapper'] ? 'AssetMapper' : null,
        ]);

        if ($tools) {
            $io->newLine();
            $io->listing($tools);
        }
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function generateGitHubActions(Generator $generator, array $profile): void
    {
        $databaseType = $profile['database_type'];
        $databaseUrl = null !== $databaseType && 'sqlite' !== $databaseType
            ? self::buildDatabaseUrl($databaseType, '127.0.0.1')
            : null;

        $variables = array_merge($profile, [
            'database_url' => $databaseUrl,
        ]);

        $generator->generateFile(
            '.github/workflows/ci.yaml',
            'ci/github_ci.yaml.tpl.php',
            $variables,
        );

        if (!$this->fileManager->fileExists('.github/dependabot.yml') && !$this->fileManager->fileExists('.github/dependabot.yaml')) {
            $generator->generateFile(
                '.github/dependabot.yml',
                'ci/github_dependabot.yml.tpl.php',
            );
        }
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function generateGitLabCi(Generator $generator, array $profile): void
    {
        $databaseType = $profile['database_type'];
        $databaseUrl = null !== $databaseType && 'sqlite' !== $databaseType
            ? self::buildDatabaseUrl($databaseType, match ($databaseType) {
                'postgres' => 'postgres',
                'mysql' => 'mysql',
                'mariadb' => 'mariadb',
                default => '127.0.0.1',
            })
            : null;

        // The official PHP images only ship pdo_sqlite
        $variables = array_merge($profile, [
            'database_url' => $databaseUrl,
            'system_packages' => array_merge(['git', 'unzip', 'libicu-dev'], 'postgres' === $databaseType ? ['libpq-dev'] : []),
            'php_extensions' => array_merge(['intl'], match ($databaseType) {
                'postgres' => ['pdo_pgsql'],
                'mysql', 'mariadb' => ['pdo_mysql'],
                default => [],
            }),
        ]);

        $generator->generateFile(
            '.gitlab-ci.yml',
            'ci/gitlab_ci.yml.tpl.php',
            $variables,
        );
    }

    private static function buildDatabaseUrl(string $databaseType, string $host): string
    {
        return match ($databaseType) {
            'postgres' => \sprintf('postgresql://app:password@%s:5432/app?serverVersion=16&charset=utf8', $host),
            'mysql' => \sprintf('mysql://root:password@%s:3306/app?serverVersion=8.0', $host),
            'mariadb' => \sprintf('mysql://root:password@%s:3306/app?serverVersion=10.11.2-MariaDB', $host),
            default => '',
        };
    }
}
