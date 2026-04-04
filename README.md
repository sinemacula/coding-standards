# Coding Standards

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

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$rules = require __DIR__ . '/vendor/sinemacula/coding-standards/php/.php-cs-fixer.rules.php';

$finder = Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config)
    ->setFinder($finder)
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules($rules);
```

### PHPCS

Create a `phpcs.xml` at your project root:

```xml
<?xml version="1.0"?>
<ruleset name="Project">
    <rule ref="vendor/sinemacula/coding-standards/php/phpcs.xml"/>
    <file>src</file>
    <file>tests</file>
</ruleset>
```

### PHPStan

#### Pure PHP projects

```neon
includes:
    - vendor/sinemacula/coding-standards/php/phpstan.neon

parameters:
    paths:
        - src
        - tests
```

#### Laravel projects

```neon
includes:
    - vendor/sinemacula/coding-standards/laravel/phpstan.neon
    - vendor/larastan/larastan/extension.neon

parameters:
    scanDirectories:
        - %currentWorkingDirectory%/src
        - %currentWorkingDirectory%/tests
    paths:
        - src
        - tests
```

### Qlty

Reference this repository as a source in your project's `.qlty/qlty.toml`:

```toml
[[source]]
name = "sinemacula"
repository = "https://github.com/sinemacula/coding-standards"
tag = "v1.0.0"
```

## What's Included

| Path                            | Tool         | Description                                        |
|---------------------------------|--------------|----------------------------------------------------|
| `php/.php-cs-fixer.rules.php`   | PHP CS Fixer | Shared rules array (PSR-12 base + org conventions) |
| `php/phpcs.xml`                 | PHPCS        | Shared ruleset (PSR-12 base + sniff exclusions)    |
| `php/phpstan.neon`              | PHPStan      | Base config (level 8, org-wide ignored errors)     |
| `laravel/phpstan.neon`          | PHPStan      | Extends base config for Laravel projects           |
| `laravel/phpstan-bootstrap.php` | PHPStan      | Qlty autoloader workaround for Larastan            |
| `markdown/.markdownlint.json`   | markdownlint | Markdown linting rules                             |
| `yaml/.yamllint.yaml`           | yamllint     | YAML linting rules                                 |

## Requirements

- PHP ^8.3
