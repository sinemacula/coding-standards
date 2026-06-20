<?php

declare(strict_types = 1);

// Standalone PSR-4 autoloader for the SineMacula\CodingStandards namespace (-> src/).
// The dogfood phpcs config (phpcs.xml) loads this so the in-repo sniff base class
// resolves without the project's vendor/autoload.php, which qlty Cloud does not
// produce (it never runs `composer install`).

spl_autoload_register(static function (string $class): void {
    $prefix = 'SineMacula\CodingStandards\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $file = dirname(__DIR__, 2) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});
