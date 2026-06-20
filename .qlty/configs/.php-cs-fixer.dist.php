<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

// qlty's pre-commit `qlty fmt` runs against a sparse worktree of only the staged
// files, so the shared rules file may be absent there. Fall back to an empty rule
// set (a no-op) rather than failing on the require; the full `qlty check` and qlty
// Cloud run against the complete tree and apply the real rules.
$rulesFile = dirname(__DIR__, 2) . '/php/.php-cs-fixer.rules.php';
$rules     = is_file($rulesFile) ? require $rulesFile : []; // NOSONAR

// This repo's PHP is PHP_CodeSniffer sniff code. The shared rules promote
// docblock @param types to native types, but Sniff::process(File, $stackPtr)
// leaves $stackPtr untyped and PHP fatals if it is narrowed to int, so the
// promotion is disabled here. The rule is unchanged for consumers.
if ($rules !== []) {
    $rules['phpdoc_to_param_type'] = false;
}

// Filter to the directories that exist; in qlty's sparse pre-commit worktree the
// source trees may be absent, and Finder::in() throws on a missing directory.
$dirs = array_values(array_filter([
    dirname(__DIR__, 2) . '/src',
    dirname(__DIR__, 2) . '/SineMacula/Sniffs',
    dirname(__DIR__, 2) . '/SineMacula/Tests',
], 'is_dir'));

$finder = Finder::create()
    ->in($dirs !== [] ? $dirs : [dirname(__DIR__, 2)])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config('SineMacula'))
    ->setFinder($finder)
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules($rules);
