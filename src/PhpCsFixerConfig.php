<?php

declare(strict_types = 1);

namespace SineMacula\CodingStandards;

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

/**
 * PHP CS Fixer configuration factory.
 *
 * Provides a factory for building PHP CS Fixer configurations using the shared
 * Sine Macula coding standards.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @SuppressWarnings("php:S4833")
 * @SuppressWarnings("php:S2003")
 */
final class PhpCsFixerConfig
{
    /**
     * Create a PHP CS Fixer configuration using shared Sine Macula rules.
     *
     * @param  array<int, string>  $directories
     * @param  array<string, mixed>  $overrides
     * @return \PhpCsFixer\Config
     */
    public static function make(array $directories, array $overrides = []): Config
    {
        $rules = require __DIR__ . '/../php/.php-cs-fixer.rules.php';

        $finder = Finder::create()
            ->in($directories)
            ->name('*.php')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true);

        return (new Config('SineMacula'))
            ->setFinder($finder)
            ->setUsingCache(true)
            ->setRiskyAllowed(true)
            ->setParallelConfig(ParallelConfigFactory::detect())
            ->setRules(array_merge($rules, $overrides));
    }
}
