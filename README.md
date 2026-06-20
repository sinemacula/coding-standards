# Coding Standards

[![Latest Stable Version](https://img.shields.io/packagist/v/sinemacula/coding-standards.svg)](https://packagist.org/packages/sinemacula/coding-standards)
[![npm Version](https://img.shields.io/npm/v/@sinemacula/coding-standards.svg)](https://www.npmjs.com/package/@sinemacula/coding-standards)
[![Maintainability](https://qlty.sh/gh/sinemacula/projects/coding-standards/maintainability.svg)](https://qlty.sh/gh/sinemacula/projects/coding-standards)
[![Code Coverage](https://qlty.sh/gh/sinemacula/projects/coding-standards/coverage.svg)](https://qlty.sh/gh/sinemacula/projects/coding-standards)
[![Total Downloads](https://img.shields.io/packagist/dt/sinemacula/coding-standards.svg)](https://packagist.org/packages/sinemacula/coding-standards)

Centralized coding standards, static analysis configurations, and code quality tooling for all Sine Macula repositories.

This package ships config files only - no runtime dependencies. Consuming projects install the tools themselves.

## Installation

### Composer (PHP-side: PHP CS Fixer, PHPStan, PHPCS)

```bash
composer require --dev sinemacula/coding-standards
```

### npm (JS-side: Biome, Knip)

```bash
npm install --save-dev @sinemacula/coding-standards
```

The npm package ships only the static configs (`js/`, `markdown/`, `yaml/`, `shell/`, `security/`). The PHP autoloaded code lives in the Composer package.

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

The `SineMacula` coding standard is auto-discovered via the `phpcodesniffer-standard` composer type. Create a
`phpcs.xml` at your project root:

```xml
<?xml version="1.0"?>
<ruleset name="Project">
    <rule ref="SineMacula"/>
    <file>src</file>
    <file>tests</file>
</ruleset>
```

### PHPStan

The shared PHPStan configs are auto-included via the `extra.phpstan.includes` section in `composer.json`. Your project's
`phpstan.neon` only needs project-specific settings:

```neon
parameters:
    level: 8
    paths:
        - src
        - tests
```

#### Laravel projects

Documentation for Laravel/Larastan integration is coming soon.

### Biome (JavaScript / TypeScript)

After installing the npm package, extend the shared Biome config from your project's `biome.json` (or `.qlty/configs/biome.json` when wired through Qlty):

```json
{
    "$schema": "https://biomejs.dev/schemas/2.0.0/schema.json",
    "root": true,
    "extends": ["@sinemacula/coding-standards/js/biome.json"],
    "files": {
        "ignoreUnknown": true,
        "includes": ["**", "!**/node_modules/**", "!**/vendor/**"]
    }
}
```

`extends` paths are resolved through normal Node module lookup, so the package only needs to be installed (no path math against `node_modules/` required). Project-specific `files.includes` and `files.excludes` stay in the consumer config.

### Knip (JavaScript / TypeScript)

```json
{
    "$schema": "https://unpkg.com/knip@6/schema.json",
    "extends": ["@sinemacula/coding-standards/js/knip.json"]
}
```

### Qlty

Reference this repository as a source in your project's `.qlty/qlty.toml`:

```toml
[[source]]
name = "sinemacula"
repository = "https://github.com/sinemacula/coding-standards"
tag = "v1.3.0"
```

## What's Included

| Path                          | Tool         | Description                                            |
|-------------------------------|--------------|--------------------------------------------------------|
| `src/PhpCsFixerConfig.php`    | PHP CS Fixer | Factory class for building PHP CS Fixer configurations |
| `php/.php-cs-fixer.rules.php` | PHP CS Fixer | Shared rules array (PSR-12 base + org conventions)     |
| `SineMacula/ruleset.xml`      | PHPCS        | Auto-discovered coding standard (PSR-12 + exclusions)  |
| `php/phpstan.neon`            | PHPStan      | Base config (org-wide ignored errors + settings)       |
| `js/biome.json`               | Biome        | JavaScript / TypeScript formatter + linter rules       |
| `js/knip.json`                | Knip         | Unused-export detection rules                          |
| `markdown/.markdownlint.json` | markdownlint | Markdown linting rules                                 |
| `yaml/.yamllint.yaml`         | yamllint     | YAML linting rules                                     |
| `shell/.shellcheckrc`         | ShellCheck   | Shell script linting rules                             |
| `security/.gitleaks.toml`     | Gitleaks     | Secret-detection ruleset                               |
| `editorconfig/.editorconfig-checker.json` | editorconfig-checker | Disables only the max-line-length check (formatters own wrapping) |

## Contributing

Contributions are welcome via GitHub pull requests.

## Security

If you discover a security issue, please contact Sine Macula directly rather than opening a public issue.

## License

Licensed under the [Apache License, Version 2.0](https://www.apache.org/licenses/LICENSE-2.0).
