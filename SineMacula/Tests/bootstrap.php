<?php

declare(strict_types = 1);

// Sets up the constants and token tables PHP_CodeSniffer needs outside its CLI.
use PHP_CodeSniffer\Util\Tokens;
use PhpParser\Lexer;

$root = dirname(__DIR__, 2);

require_once $root . '/vendor/autoload.php';

// php-parser must define the polyfilled token constants as integers before
// PHP_CodeSniffer defines them as strings, or coverage runs fail.
class_exists(Lexer::class);

// Composer's PSR-4 map does not cover the PHP_CodeSniffer namespace.
require_once $root . '/vendor/squizlabs/php_codesniffer/autoload.php';

if (defined('PHP_CODESNIFFER_VERBOSITY') === false) {
    define('PHP_CODESNIFFER_VERBOSITY', 0);
}

if (defined('PHP_CODESNIFFER_CBF') === false) {
    define('PHP_CODESNIFFER_CBF', false);
}

// Defines custom token constants, such as T_ENUM_CASE, that sniffs reference.
class_exists(Tokens::class);
