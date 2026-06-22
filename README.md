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

The npm package ships only the static configs (`js/`, `markdown/`, `yaml/`, `shell/`, `security/`). The PHP autoloaded
code lives in the Composer package.

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

For Laravel projects, also install
[`sinemacula/coding-standards-laravel`](https://github.com/sinemacula/coding-standards-laravel) and reference its
`SineMaculaLaravel` PHPCS standard (which includes this one) in place of `SineMacula`. It adds the
Laravel-specific sniffs and PHPStan rules; see that package's README for setup.

### Biome (JavaScript / TypeScript)

After installing the npm package, extend the shared Biome config from your project's `biome.json` (or
`.qlty/configs/biome.json` when wired through Qlty):

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

`extends` paths are resolved through normal Node module lookup, so the package only needs to be installed (no path
math against `node_modules/` required). Project-specific `files.includes` and `files.excludes` stay in the consumer
config.

### Knip (JavaScript / TypeScript)

```json
{
    "$schema": "https://unpkg.com/knip@6/schema.json",
    "extends": ["@sinemacula/coding-standards/js/knip.json"]
}
```

### Qlty

Reference this repository as a source in your project's `.qlty/qlty.toml`, pinning `tag` to the latest
[release](https://github.com/sinemacula/coding-standards/releases):

```toml
[[source]]
name = "sinemacula"
repository = "https://github.com/sinemacula/coding-standards"
tag = "<version>"
```

## What's Included

| Path                          | Tool         | Description                                            |
|-------------------------------|--------------|--------------------------------------------------------|
| `src/PhpCsFixerConfig.php`    | PHP CS Fixer | Factory class for building PHP CS Fixer configurations |
| `php/.php-cs-fixer.rules.php` | PHP CS Fixer | Shared rules array (PSR-12 base + org conventions)     |
| `SineMacula/ruleset.xml`      | PHPCS        | Auto-discovered coding standard (PSR-12 + exclusions)  |
| `php/phpstan-base.neon`       | PHPStan      | Base config (org-wide ignored errors + settings)       |
| `js/biome.json`               | Biome        | JavaScript / TypeScript formatter + linter rules       |
| `js/knip.json`                | Knip         | Unused-export detection rules                          |
| `markdown/.markdownlint.json` | markdownlint | Markdown linting rules                                 |
| `yaml/.yamllint.yaml`         | yamllint     | YAML linting rules                                     |
| `shell/.shellcheckrc`         | ShellCheck   | Shell script linting rules                             |
| `security/.gitleaks.toml`     | Gitleaks     | Secret-detection ruleset                               |
| `editorconfig/.editorconfig-checker.json` | editorconfig-checker | Disables only the max-line-length check |

## Rules

These are the custom rules this package enforces on top of PSR-12. A deliberate exception can be bypassed with the
native directive - `// phpcs:ignore <code>` for a sniff, `@phpstan-ignore <identifier>` for a rule.

### PHPCS sniffs

| Sniff | Enforces |
|-------|----------|
| `SineMacula.Attributes.DisallowToolingAttribute` | No IDE/tooling attributes (e.g. `JetBrains\PhpStorm`). |
| `SineMacula.Classes.RequireFinalClass` | Concrete classes must be `final` or `abstract` (`@inheritable` opts out). |
| `SineMacula.Classes.RequireReadonlyPublicProperty` | Public properties (declared or promoted) must be `readonly`. |
| `SineMacula.Commenting.CommentLineLength` | Standalone comment lines must not exceed 80 chars (FQCN/URL exempt). |
| `SineMacula.Commenting.ConsistentEnumCaseComments` | Enum case docs are all-or-nothing within an enum. |
| `SineMacula.Commenting.RequireConstantComment` | Every class/interface/enum/trait constant needs a doc comment. |
| `SineMacula.Commenting.RequireCopyrightTag` | Class/interface/enum/trait docblocks must carry an `@copyright` tag. |
| `SineMacula.Commenting.RequireNonPromotedParameterComment` | Plain params mixed with promoted ones need a comment. |
| `SineMacula.Commenting.RequirePromotedPropertyComment` | Every constructor-promoted property needs a doc comment. |
| `SineMacula.Exceptions.DisallowBaseException` | No throwing the base `\Exception`; throw a domain exception. |
| `SineMacula.Functions.RequireSensitiveParameter` | Secret-named params need `#[\SensitiveParameter]`. |
| `SineMacula.Metrics.MaxMethodCount` | A class/interface/trait/enum may declare at most 20 methods. |
| `SineMacula.Metrics.MethodLength` | A method body may have at most 50 significant lines. |
| `SineMacula.Namespaces.RequireConcernsNamespace` | Traits must live under a `Concerns` namespace segment. |
| `SineMacula.Namespaces.RequireContractsNamespace` | Interfaces must live under a `Contracts` namespace segment. |
| `SineMacula.Namespaces.RequireEnumsNamespace` | Enums must live under an `Enums` namespace segment. |
| `SineMacula.NamingConventions.BooleanMethodName` | `bool` methods are predicates; command verbs/@imperative exempt. |
| `SineMacula.NamingConventions.DisallowInterfacePrefix` | Interface names must not use the Hungarian `I` prefix. |
| `SineMacula.NamingConventions.ValidEnumCaseName` | Enum cases must be `SCREAMING_SNAKE_CASE`. |
| `SineMacula.NamingConventions.ValidGlobalFunctionName` | Global functions must be declared in `snake_case`. |
| `SineMacula.TypeHints.RequireConstantType` | Class/interface/enum/trait constants must declare a native type. |

### PHPStan rules

| Identifier | Enforces |
|------------|----------|
| `sineMacula.mutableStaticProperty` | No mutable static state; use instance state or a constant instead. |

## Requirements

- PHP ^8.3 (Composer package)
- Node.js (npm package)

## Testing

```bash
composer test           # PHPUnit suite for the custom sniffs and PHPStan rule
composer test:coverage  # suite with Clover coverage output
composer analyse        # PHPStan static analysis
composer check          # static analysis and lint via qlty
composer format         # format via qlty
composer smells         # duplication / complexity smells via qlty
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of notable changes.

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines on branching, commits, code
quality, and pull requests.

## Security

If you discover a security vulnerability, please report it responsibly. See [SECURITY.md](SECURITY.md) for the
disclosure policy and contact details.

## License

Licensed under the [Apache License, Version 2.0](https://www.apache.org/licenses/LICENSE-2.0).
