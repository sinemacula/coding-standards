<?php

declare(strict_types = 1);

namespace SineMacula\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Tests that the fixer sandbox is given everything it needs, and nothing that
 * cannot run on the declared PHP floor.
 *
 * A project's php-cs-fixer sandbox is installed from the `extra_packages` on
 * its own plugin entry, not from the project's own manifest, so what is listed
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

    /** The package a project's fixer config autoloads its rules from. */
    private const string PACKAGE = 'sinemacula/coding-standards';

    /**
     * The sandbox is given the standards package, since a project's fixer
     * config autoloads its rules from there and nothing else supplies it.
     *
     * @return void
     */
    public function testTheDocumentedWiringGivesTheSandboxTheStandardsPackage(): void
    {
        self::assertArrayHasKey(self::PACKAGE, $this->documented());
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
                sprintf('%s is in php-cs-fixer\'s tree but is not pinned for the sandbox.', $package),
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
     * The entry a project adds for this package sits in a file no dependency
     * manager reads, so the shared preset carries the rule that raises it. The
     * rule has to select that file, name this package, read it from the right
     * registry, and capture an exact version: a constraint resolves to the
     * newest release it already allows, so a range leaves nothing to raise.
     *
     * @return void
     */
    public function testTheSharedPresetRaisesTheDocumentedStandardsEntry(): void
    {
        $version = $this->documented()[self::PACKAGE] ?? '';
        $entry   = sprintf('"%s@%s"', self::PACKAGE, $version);

        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $version, 'The documented entry must name an exact version.');

        foreach ($this->presetRules() as $rule) {
            if ($this->targets($rule) && $this->captures($rule, $entry, $version)) {
                return;
            }
        }

        self::fail('No rule in the shared preset raises the entry the README tells a project to add.');
    }

    /**
     * Whether the given rule is pointed at this package, in the file the entry
     * lives in, and at the registry the entry is written for.
     *
     * @param  array<mixed, mixed>  $rule
     * @return bool
     */
    private function targets(array $rule): bool
    {
        return ($rule['depNameTemplate'] ?? null)    === self::PACKAGE
            && ($rule['datasourceTemplate'] ?? null) === 'packagist'
            && $this->selects((array) ($rule['fileMatch'] ?? []), '.qlty/qlty.toml');
    }

    /**
     * Whether the given rule takes the version out of the entry. Capturing it
     * is what the update is calculated from, so a rule that matches the line
     * without capturing raises nothing.
     *
     * @param  array<mixed, mixed>  $rule
     * @param  string  $entry
     * @param  string  $version
     * @return bool
     */
    private function captures(array $rule, string $entry, string $version): bool
    {
        foreach ((array) ($rule['matchStrings'] ?? []) as $pattern) {
            preg_match($this->delimited($pattern), $entry, $captured);

            if (($captured['currentValue'] ?? null) === $version) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether any of the given patterns selects the given path.
     *
     * @param  array<mixed, mixed>  $patterns
     * @param  string  $path
     * @return bool
     */
    private function selects(array $patterns, string $path): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($this->delimited($pattern), $path) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * A pattern from the preset, delimited for use here. The preset stores it
     * bare, as the tool that consumes it takes no delimiters.
     *
     * @param  mixed  $pattern
     * @return string
     */
    private function delimited(mixed $pattern): string
    {
        return '/' . str_replace('/', '\/', is_string($pattern) ? $pattern : '') . '/';
    }

    /**
     * The rules the shared preset applies to files it is pointed at.
     *
     * @return array<int, array<mixed, mixed>>
     */
    private function presetRules(): array
    {
        $contents = (string) file_get_contents(dirname(__DIR__, 2) . '/default.json');
        $preset   = (array) json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return array_map(
            static fn (mixed $rule): array => (array) $rule,
            array_values((array) ($preset['customManagers'] ?? [])),
        );
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
