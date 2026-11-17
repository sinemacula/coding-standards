<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

// qlty's pre-commit fmt runs on a sparse worktree of the staged files, where
// the rules file may be absent.
$rulesFile = dirname(__DIR__, 2) . '/php/.php-cs-fixer.rules.php';
$rules     = is_file($rulesFile) ? require $rulesFile : []; // NOSONAR

// Sniff::process() leaves $stackPtr untyped, and PHP fatals if it is narrowed.
if ($rules !== []) {
    $rules['phpdoc_to_param_type'] = false;
}

// Finder::in() throws on a directory missing from the sparse worktree.
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
