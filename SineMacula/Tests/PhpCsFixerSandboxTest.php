<?php

declare(strict_types = 1);

namespace SineMacula\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Tests that the fixer sandbox is given everything it needs.
 *
 * A project's php-cs-fixer sandbox is installed from `extra_packages`, not from
 * the project's own manifest, so what is listed there is the whole of what the
 * tool gets beyond its own dependencies. It needs the standards package, which
 * a project's fixer config autoloads its rules from and which each project
 * lists on its own entry. qlty resolves the sandbox for the project's PHP
 * runtime, so the fixer's Symfony needs no pin.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @internal
 */
#[CoversNothing]
final class PhpCsFixerSandboxTest extends TestCase
{
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
     * entirely when one is present, silently.
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
            && $this->selects((array) ($rule['managerFilePatterns'] ?? []), '.qlty/qlty.toml');
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
     * Whether any of the given file patterns selects the given path. Renovate
     * reads a pattern between slashes as a regex and anything else as a glob;
     * the preset uses only regexes, so a glob selects nothing here.
     *
     * @param  array<mixed, mixed>  $patterns
     * @param  string  $path
     * @return bool
     */
    private function selects(array $patterns, string $path): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match('/^\/(.+)\/$/s', is_string($pattern) ? $pattern : '', $regex) !== 1) {
                continue;
            }

            if (preg_match($this->delimited($regex[1]), $path) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * A pattern from the preset, delimited for use here. Match strings are
     * stored bare and file patterns arrive with their own slashes removed, as
     * neither is a PHP pattern.
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
     * first extra_packages list after the given marker in the given file.
     *
     * @param  string  $path
     * @param  string  $marker
     * @return array<string, string>
     */
    private function extraPackages(string $path, string $marker): array
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/' . $path);
        $block  = (string) preg_replace('/^.*?' . preg_quote($marker, '/') . '.*?extra_packages = \[/s', '', $source);
        $list   = (string) preg_replace('/\].*$/s', '', $block);
        $found  = preg_match_all('/"([^"@]+)@([^"]+)"/', $list, $matches, PREG_SET_ORDER);

        $packages = [];

        foreach ($found === 0 ? [] : $matches as $match) {
            $packages[$match[1]] = $match[2];
        }

        return $packages;
    }
}
