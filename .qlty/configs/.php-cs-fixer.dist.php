<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$rules = require_once dirname(__DIR__, 2) . '/php/.php-cs-fixer.rules.php'; // NOSONAR

$finder = Finder::create()
    ->in([
        dirname(__DIR__, 2) . '/src',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config('SineMacula'))
    ->setFinder($finder)
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules($rules);
