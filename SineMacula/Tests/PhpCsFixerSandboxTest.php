<?php

declare(strict_types = 1);

namespace SineMacula\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Tests that the fixer sandbox is given everything it needs, and nothing that
 * cannot run on the declared PHP floor.
 *
 * A project's php-cs-fixer sandbox is installed from this source's
 * `extra_packages`, not from the project's own manifest, so what is listed
 * there is the whole of what the tool gets. It needs the standards package,
 * which is what a project's fixer config autoloads its rules from, and it needs
 * Symfony held below 8: php-cs-fixer has accepted Symfony 8 since 3.90.0, that
 * line requires PHP 8.4.1, and the sandbox install does not honour the platform
 * requirement - so without a pin a project on 8.3 installs a Symfony its runner
 * cannot parse and the plugin exits before reporting anything.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversNothing]
final class PhpCsFixerSandboxTest extends TestCase
{
    /** The Symfony constraint every pinned package is held to. */
    private const string PINNED = '^7.4';

    /**
     * The sandbox is given the standards package, since a project's fixer
     * config autoloads its rules from there and nothing else supplies it.
     *
     * @return void
     */
    public function testTheDocumentedWiringGivesTheSandboxTheStandardsPackage(): void
    {
        self::assertArrayHasKey('sinemacula/coding-standards', $this->documented());
    }

    /**
     * This repo sets no package_file on the plugin. qlty ignores extra_packages
     * entirely when one is present, silently, which would take the pins below
     * with it.
     *
     * @return void
     */
    public function testThisProjectSetsNoPackageFileOnTheFixer(): void
    {
        $config = (string) file_get_contents(dirname(__DIR__, 2) . '/.qlty/qlty.toml');
        $block  = (string) preg_replace('/^.*name = "php-cs-fixer"/s', '', $config);
        $block  = (string) preg_replace('/\n\[\[plugin\]\].*$/s', '', $block);

        self::assertStringNotContainsString('package_file', $block);
    }

    /**
     * Every Symfony package in php-cs-fixer's installed tree that tracks the
     * framework's own version line is pinned below 8, walked from the tree
     * rather than read from a list, so a dependency the fixer gains is not left
     * unguarded.
     *
     * @return void
     */
    public function testEverySymfonyInTheFixerTreeIsPinnedBelowEight(): void
    {
        $pinned = $this->pinned();

        foreach ($this->fixerSymfonyPackages() as $package) {
            self::assertSame(
                self::PINNED,
                $pinned[$package] ?? null,
                sprintf('%s is in php-cs-fixer\'s tree but is not pinned in source.toml.', $package),
            );
        }
    }

    /**
     * Nothing is pinned that the fixer does not pull, so the list does not
     * quietly outlive the tree it was written for.
     *
     * @return void
     */
    public function testNothingIsPinnedThatTheFixerDoesNotPull(): void
    {
        $symfony = array_filter(
            array_keys($this->pinned()),
            static fn (string $package): bool => str_starts_with($package, 'symfony/'),
        );

        self::assertSame($this->fixerSymfonyPackages(), array_values($symfony));
    }

    /**
     * The standards entry a project adds is kept current by the shared Renovate
     * preset. It sits in a config file no dependency manager reads, so without
     * a matching rule it would be the one version in a project that nobody
     * bumps. Matching is not enough on its own: the version has to be captured,
     * since that is what the update is calculated from.
     *
     * @return void
     */
    public function testRenovateCapturesTheVersionFromTheDocumentedStandardsEntry(): void
    {
        $version  = $this->documentedStandardsVersion();
        $entry    = sprintf('"sinemacula/coding-standards@%s"', $version);
        $captured = [];

        foreach ($this->renovateMatchStrings() as $pattern) {
            if (preg_match('/' . str_replace('/', '\/', $pattern) . '/', $entry, $matches) !== 1) {
                continue;
            }

            $captured[] = $matches['currentValue'] ?? '';
        }

        self::assertContains($version, $captured, 'No Renovate manager captures the version from the entry the README tells a project to add.');
    }

    /**
     * The documented version is exact rather than a range. A range resolves to
     * the newest release it already allows, which leaves nothing to raise it
     * to, so the entry would sit at its original floor forever while appearing
     * to be managed.
     *
     * @return void
     */
    public function testTheDocumentedStandardsEntryIsAnExactVersion(): void
    {
        self::assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+$/',
            $this->documentedStandardsVersion(),
            'The standards entry must be an exact version; a range is never raised.',
        );
    }

    /**
     * The version the README tells a project to pin the standards package to.
     *
     * @return string
     */
    private function documentedStandardsVersion(): string
    {
        return $this->documented()['sinemacula/coding-standards'] ?? '';
    }

    /**
     * Every pattern the shared Renovate preset matches against.
     *
     * @return array<int, string>
     */
    private function renovateMatchStrings(): array
    {
        $preset   = $this->decode('default.json');
        $managers = $preset['customManagers'] ?? [];
        $patterns = [];

        foreach (is_array($managers) ? $managers : [] as $manager) {
            if (is_array($manager) === false) {
                continue;
            }

            foreach ($this->stringList($manager['matchStrings'] ?? null) as $pattern) {
                $patterns[] = $pattern;
            }
        }

        return $patterns;
    }

    /**
     * Keep only the string entries of a decoded list.
     *
     * @param  mixed  $list
     * @return array<int, string>
     */
    private function stringList(mixed $list): array
    {
        $strings = [];

        foreach (is_array($list) ? $list : [] as $entry) {
            if (is_string($entry) === false) {
                continue;
            }

            $strings[] = $entry;
        }

        return $strings;
    }

    /**
     * Decode a JSON file from the repository root.
     *
     * @param  string  $path
     * @return array<mixed, mixed>
     */
    private function decode(string $path): array
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/' . $path);
        $decoded  = json_decode($contents === false ? '' : $contents, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * What this repo pins for its own sandbox.
     *
     * @return array<string, string>
     */
    private function pinned(): array
    {
        return $this->extraPackages('.qlty/qlty.toml', 'name = "php-cs-fixer"');
    }

    /**
     * What the README tells a project to pin for its sandbox.
     *
     * @return array<string, string>
     */
    private function documented(): array
    {
        return $this->extraPackages('README.md', 'name = "php-cs-fixer"');
    }

    /**
     * The `name@constraint` entries added to the fixer sandbox, read from the
     * given file's php-cs-fixer block.
     *
     * @param  string  $path
     * @param  string  $marker
     * @return array<string, string>
     */
    private function extraPackages(string $path, string $marker): array
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/' . $path);
        $block  = (string) preg_replace('/^.*' . preg_quote($marker, '/') . '/s', '', $source);
        $list   = (string) preg_replace('/\].*$/s', '', $block);
        $found  = preg_match_all('/"([^"@]+)@([^"]+)"/', $list, $matches, PREG_SET_ORDER);

        $packages = [];

        foreach ($found === 0 ? [] : $matches as $match) {
            $packages[$match[1]] = $match[2];
        }

        return $packages;
    }

    /**
     * Every Symfony package in php-cs-fixer's installed tree that tracks the
     * framework's own version line. The polyfills exist to support old PHP and
     * the `-contracts` packages carry their own 3.x line, so neither reaches 8.
     *
     * @return array<int, string>
     */
    private function fixerSymfonyPackages(): array
    {
        $installed = $this->installedRequirements();
        $packages  = [];
        $pending   = ['friendsofphp/php-cs-fixer'];
        $seen      = [];

        while ($pending !== []) {
            $name = (string) array_pop($pending);

            if (isset($seen[$name]) || isset($installed[$name]) === false) {
                continue;
            }

            $seen[$name] = true;
            $pending     = [...$pending, ...array_keys($installed[$name])];

            if ($this->tracksTheSymfonyLine($name) === false) {
                continue;
            }

            $packages[] = $name;
        }

        sort($packages);

        return $packages;
    }

    /**
     * Whether a package is versioned along with the Symfony framework itself.
     *
     * @param  string  $name
     * @return bool
     */
    private function tracksTheSymfonyLine(string $name): bool
    {
        return str_starts_with($name, 'symfony/')
            && str_starts_with($name, 'symfony/polyfill-') === false
            && str_ends_with($name, '-contracts')          === false;
    }

    /**
     * The requirements of every installed package, keyed by package name.
     *
     * @return array<string, array<string, string>>
     */
    private function installedRequirements(): array
    {
        $tree = [];

        foreach ($this->installedPackages() as $package) {
            if (is_array($package) === false || is_string($package['name'] ?? null) === false) {
                continue;
            }

            $tree[$package['name']] = $this->stringMap($package['require'] ?? null);
        }

        return $tree;
    }

    /**
     * The package entries composer recorded for the installed tree.
     *
     * @return array<int|string, mixed>
     */
    private function installedPackages(): array
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/vendor/composer/installed.json');
        $decoded  = json_decode($contents === false ? '' : $contents, true, 512, JSON_THROW_ON_ERROR);
        $packages = is_array($decoded) ? $decoded['packages'] ?? $decoded : [];

        return is_array($packages) ? $packages : [];
    }

    /**
     * Keep only the entries of a decoded section that are string pairs.
     *
     * @param  mixed  $section
     * @return array<string, string>
     */
    private function stringMap(mixed $section): array
    {
        $entries = [];

        foreach (is_array($section) ? $section : [] as $name => $constraint) {
            if (is_string($name) === false || is_string($constraint) === false) {
                continue;
            }

            $entries[$name] = $constraint;
        }

        return $entries;
    }
}
