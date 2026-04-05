<?php

/**
 * Register PSR-4 autoloaders for the qlty PHPStan sandbox.
 *
 * Qlty strips the autoload section from composer.json when installing tools in
 * its sandbox environment. This bootstrap file reads the project's
 * composer.json from the workspace root and restores the PSR-4 mappings so
 * Larastan can resolve application classes during analysis.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 *
 * @SuppressWarnings("php:S4833")
 * @SuppressWarnings("php:S2003")
 */
$composerFile = getcwd() . '/composer.json';

if (!file_exists($composerFile)) {
    return;
}

$config   = json_decode(file_get_contents($composerFile), true);
$basePath = getcwd();
$mappings = array_merge(
    $config['autoload']['psr-4']     ?? [],
    $config['autoload-dev']['psr-4'] ?? [],
);

foreach ($mappings as $prefix => $directory) {

    $prefixLength = strlen($prefix);
    $baseDir      = $basePath . '/' . rtrim($directory, '/') . '/';

    spl_autoload_register(static function (string $class) use ($prefix, $prefixLength, $baseDir): void {

        if (strncmp($class, $prefix, $prefixLength) !== 0) {
            return;
        }

        $file = $baseDir . str_replace('\\', '/', substr($class, $prefixLength)) . '.php';

        if (file_exists($file)) {
            require $file; // NOSONAR
        }
    });
}
