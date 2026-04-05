# Coding Standards

[![Latest Stable Version](https://img.shields.io/packagist/v/sinemacula/coding-standards.svg)](https://packagist.org/packages/sinemacula/coding-standards)
[![Maintainability](https://qlty.sh/gh/sinemacula/projects/coding-standards/maintainability.svg)](https://qlty.sh/gh/sinemacula/projects/coding-standards)
[![Code Coverage](https://qlty.sh/gh/sinemacula/projects/coding-standards/coverage.svg)](https://qlty.sh/gh/sinemacula/projects/coding-standards)
[![Total Downloads](https://img.shields.io/packagist/dt/sinemacula/coding-standards.svg)](https://packagist.org/packages/sinemacula/coding-standards)

Centralized coding standards, static analysis configurations, and code quality tooling for all Sine Macula repositories.

This package ships config files only — no runtime dependencies. Consuming projects install the tools themselves.

## Installation

### Composer

```bash
composer require --dev sinemacula/coding-standards
```

## Usage

Each consuming project creates thin wrapper files at its root that reference the shared configs.

### PHP CS Fixer

Create a `.php-cs-fixer.dist.php` at your project root:

```php
<?php

use SineMacula\CodingStandards\PhpCsFixerConfig;

return PhpCsFixerConfig::make([
    __DIR__ . '/src',
    __DIR__ . '/tests',
]);
```

You can pass rule overrides as a second argument:

```php
return PhpCsFixerConfig::make(
    [__DIR__ . '/src', __DIR__ . '/tests'],
    ['strict_comparison' => false],
);
```

### PHPCS

The `SineMacula` coding standard is auto-discovered via the `phpcodesniffer-standard` composer type. Create a `phpcs.xml` at your project root:

```xml
<?xml version="1.0"?>
<ruleset name="Project">
    <rule ref="SineMacula"/>
    <file>src</file>
    <file>tests</file>
</ruleset>
```

### PHPStan

The shared PHPStan configs are auto-included via the `extra.phpstan.includes` section in `composer.json`. Your project's `phpstan.neon` only needs project-specific settings:

```neon
parameters:
    level: 8
    paths:
        - src
        - tests
```

#### Laravel projects

Documentation for Laravel/Larastan integration is coming soon.

### Qlty

Reference this repository as a source in your project's `.qlty/qlty.toml`:

```toml
[[source]]
name = "sinemacula"
repository = "https://github.com/sinemacula/coding-standards"
tag = "v1.0.0"
```

## What's Included

| Path                            | Tool         | Description                                             |
|---------------------------------|--------------|---------------------------------------------------------|
| `src/PhpCsFixerConfig.php`      | PHP CS Fixer | Factory class for building PHP CS Fixer configurations  |
| `php/.php-cs-fixer.rules.php`   | PHP CS Fixer | Shared rules array (PSR-12 base + org conventions)      |
| `SineMacula/ruleset.xml`        | PHPCS        | Auto-discovered coding standard (PSR-12 + exclusions)   |
| `php/phpstan.neon`              | PHPStan      | Base config (org-wide ignored errors + settings)        |
| `laravel/phpstan.neon`          | PHPStan      | Laravel bootstrap config for Larastan                   |
| `laravel/phpstan-bootstrap.php` | PHPStan      | Dynamic PSR-4 autoloader for qlty sandbox               |
| `markdown/.markdownlint.json`   | markdownlint | Markdown linting rules                                  |
| `yaml/.yamllint.yaml`           | yamllint     | YAML linting rules                                      |
| `shell/.shellcheckrc`           | ShellCheck   | Shell script linting rules                              |

## Contributing

Contributions are welcome via GitHub pull requests.

## Security

If you discover a security issue, please contact Sine Macula directly rather than opening a public issue.

## License

Licensed under the [Apache License, Version 2.0](https://www.apache.org/licenses/LICENSE-2.0).
