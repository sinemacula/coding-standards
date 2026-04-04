<?php

/*
 * Register the App namespace autoloader for qlty's PHPStan sandbox.
 *
 * Qlty strips the autoload section from composer.json when installing tools.
 * This bootstrap file restores the PSR-4 mapping so Larastan can resolve App\
 * classes during analysis.
 *
 * @SuppressWarnings("php:S4833")
 * @SuppressWarnings("php:S2003")
 */

spl_autoload_register(static function (string $class): void {
    $prefix  = 'App\\';
    $baseDir = dirname(__DIR__, 2) . '/modules/';
    $len     = strlen($prefix);

    if (strncmp($class, $prefix, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file          = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
