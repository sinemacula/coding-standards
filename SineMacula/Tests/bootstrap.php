<?php

/**
 * PHPUnit bootstrap for the Sine Macula sniff test suite.
 *
 * Loads the Composer autoloader and the runtime constants / token tables that
 * PHP_CodeSniffer expects when its Config, Ruleset and File classes are used
 * directly (rather than through the phpcs CLI runner).
 */

$root = dirname(__DIR__, 2);

require_once $root . '/vendor/autoload.php';

// PHP_CodeSniffer ships its own autoloader; Composer's PSR-4 map does not cover
// the PHP_CodeSniffer namespace.
require_once $root . '/vendor/squizlabs/php_codesniffer/autoload.php';

if (defined('PHP_CODESNIFFER_VERBOSITY') === false) {
    define('PHP_CODESNIFFER_VERBOSITY', 0);
}

if (defined('PHP_CODESNIFFER_CBF') === false) {
    define('PHP_CODESNIFFER_CBF', false);
}

// Loading the Tokens class defines PHP_CodeSniffer's custom token constants
// (such as T_ENUM_CASE) that sniffs reference before any file is processed.
$tokens = new PHP_CodeSniffer\Util\Tokens();
