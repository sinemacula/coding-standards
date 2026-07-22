# Coding Standards

[![Latest Stable Version](https://img.shields.io/packagist/v/sinemacula/coding-standards.svg)](https://packagist.org/packages/sinemacula/coding-standards)
[![npm Version](https://img.shields.io/npm/v/@sinemacula/coding-standards.svg)](https://www.npmjs.com/package/@sinemacula/coding-standards)
[![Quality Gates](https://github.com/sinemacula/coding-standards/actions/workflows/quality-gates.yml/badge.svg?branch=master)](https://github.com/sinemacula/coding-standards/actions/workflows/quality-gates.yml)
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
    "extends": [
        "@sinemacula/coding-standards/js/biome.json"
    ],
    "files": {
        "ignoreUnknown": true,
        "includes": [
            "**",
            "!**/node_modules/**",
            "!**/vendor/**"
        ]
    }
}
```

`extends` paths are resolved through normal Node module lookup, so the package only needs to be installed (no path
math against `node_modules/` required). Project-specific `files.includes` and `files.excludes` stay in the consumer
config.

### ESLint (JavaScript / TypeScript)

ESLint runs *alongside* Biome, not in place of it. Biome keeps owning formatting and the fast syntactic lint; ESLint
adds only the two things Biome structurally cannot express: this package's custom structural rules and the opt-in
type-aware rules (the curated typescript-eslint set plus the type-driven custom rules). Add the linter, the
typescript-eslint tooling, and this package to your dev dependencies:

```bash
npm install --save-dev eslint typescript typescript-eslint eslint-plugin-jsdoc @sinemacula/coding-standards
```

The package exposes three flat-config entry points:

- `@sinemacula/coding-standards/js/eslint` - the base layer of syntax-only custom rules; needs no `tsconfig`, so it
  stays cheap and runs anywhere Biome runs.
- `@sinemacula/coding-standards/js/eslint/type-checked` - the opt-in type-aware layer. It includes the base layer and
  adds the cross-file / type-driven rules, so it needs a consumer `tsconfig`; use it in place of the base layer where
  one exists.
- `@sinemacula/coding-standards/js/eslint/vue` - the opt-in Vue layer for single-file components. Unlike the
  type-aware layer it carries no base rules of its own, so spread it *alongside* whichever layer the repository
  already uses rather than in place of one.

Create an `eslint.config.js` (or `.qlty/configs/eslint.config.js` when wired through Qlty) that spreads the layer you
want. Without a `tsconfig`, use the base layer:

```js
import sm from '@sinemacula/coding-standards/js/eslint';

export default [...sm];
```

Where a `tsconfig` exists, use the type-aware layer instead (it already carries the base rules):

```js
import typeChecked from '@sinemacula/coding-standards/js/eslint/type-checked';

export default [...typeChecked];
```

Vue repositories add the Vue toolchain and spread the Vue layer after the layer they already use:

```bash
npm install --save-dev eslint-plugin-vue vue-eslint-parser eslint-plugin-check-file
```

```js
import typeChecked from '@sinemacula/coding-standards/js/eslint/type-checked';
import vue from '@sinemacula/coding-standards/js/eslint/vue';

export default [...typeChecked, ...vue];
```

The Vue layer registers the single-file-component parser (without it `.vue` files are not linted at all), resolves
`<script lang="ts">` blocks through the TypeScript parser, and holds component filenames to kebab-case. It also
carries the template layout rules, which is the one place ESLint takes on formatting: Biome does not understand
single-file components, so `.vue` markup would otherwise go unformatted entirely. Those rules are aligned to the
shared four-space indent.

When wiring ESLint through Qlty, the shared eslint plugin sandbox installs only `eslint`, `jest`, and `prettier` by
default, so the flat config's imports of this package and `typescript-eslint` fail to resolve. Widen the install
filter in your `.qlty/qlty.toml` so the sandbox carries them (this repository's `source.toml` exports the same
override, but source-exported plugin definitions do not reliably propagate, so mirror it consumer-side):

```toml
[plugins.definitions.eslint]
package_filters = ["@sinemacula/coding-standards", "typescript-eslint", "@typescript-eslint", "eslint-plugin-jsdoc"]
```

Repositories enabling the Vue layer widen the same filter further, since its plugins have to resolve inside that
sandbox too:

```toml
[plugins.definitions.eslint]
package_filters = [
    "@sinemacula/coding-standards", "typescript-eslint", "@typescript-eslint", "eslint-plugin-jsdoc",
    "eslint-plugin-vue", "vue-eslint-parser", "eslint-plugin-check-file",
]
```

### TypeScript (tsconfig)

The package ships a shared `tsconfig` base so every TypeScript repository checks its code to the same bar. Install the
package (as above) and extend the base from your `tsconfig.json`:

```json
{
    "extends": "@sinemacula/coding-standards/js/tsconfig.base.json",
    "compilerOptions": {
        "lib": ["ES2023", "DOM", "DOM.Iterable"],
        "types": ["node"]
    },
    "include": ["src"]
}
```

The base carries only the environment-independent options: the full strictness set and the module/resolution
discipline. Everything environment-specific stays in the consuming repo and layers on top - `lib` (DOM for the browser,
none for a Node service), `types`, Vue's `jsx`/`jsxImportSource`, `paths`, `noEmit`, and the `include`/`exclude` globs.
The `target` and `module` defaults suit bundler-built apps and libraries; a non-bundler project overrides them.

The base sets `noPropertyAccessFromIndexSignature`, so a property that comes from an index signature is accessed with
brackets (`config['key']`), not a dot. Biome cannot see types and so cannot tell that access apart from a normal one,
which is why its `useLiteralKeys` rule is off and the type-aware `@typescript-eslint/dot-notation` rule enforces dot
access for real properties instead. Load the type-checked ESLint layer to get it.

### Knip (JavaScript / TypeScript)

```json
{
    "$schema": "https://unpkg.com/knip@6/schema.json",
    "extends": [
        "@sinemacula/coding-standards/js/knip.json"
    ]
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

| Path                                      | Tool                 | Description                                            |
|-------------------------------------------|----------------------|--------------------------------------------------------|
| `src/PhpCsFixerConfig.php`                | PHP CS Fixer         | Factory class for building PHP CS Fixer configurations |
| `php/.php-cs-fixer.rules.php`             | PHP CS Fixer         | Shared rules array (PSR-12 base + org conventions)     |
| `SineMacula/ruleset.xml`                  | PHPCS                | Auto-discovered coding standard (PSR-12 + exclusions)  |
| `php/phpstan-base.neon`                   | PHPStan              | Base config (org-wide ignored errors + settings)       |
| `js/biome.json`                           | Biome                | JavaScript / TypeScript formatter + linter rules       |
| `js/knip.json`                            | Knip                 | Unused-export detection rules                          |
| `js/eslint/`                              | ESLint               | Structural, type-aware + Vue rules; runs with Biome    |
| `markdown/.markdownlint.json`             | markdownlint         | Markdown linting rules                                 |
| `yaml/.yamllint.yaml`                     | yamllint             | YAML linting rules                                     |
| `shell/.shellcheckrc`                     | ShellCheck           | Shell script linting rules                             |
| `security/.gitleaks.toml`                 | Gitleaks             | Secret-detection ruleset                               |
| `editorconfig/.editorconfig-checker.json` | editorconfig-checker | Disables only the max-line-length check                |

## Rules

These are the custom rules this package enforces on top of PSR-12. A deliberate exception can be bypassed with the
native directive - `// phpcs:ignore <code>` for a sniff, `@phpstan-ignore <identifier>` for a rule,
`// eslint-disable-next-line <rule>` for an ESLint rule.

### PHPCS sniffs

| Sniff                                                      | Enforces                                                                    |
|------------------------------------------------------------|-----------------------------------------------------------------------------|
| `SineMacula.Attributes.DisallowToolingAttribute`           | No IDE/tooling attributes (e.g. `JetBrains\PhpStorm`).                      |
| `SineMacula.Classes.RequireFinalClass`                     | Concrete classes must be `final` or `abstract` (`@inheritable` opts out).   |
| `SineMacula.Classes.RequireReadonlyPublicProperty`         | Public properties (declared or promoted) must be `readonly`.                |
| `SineMacula.Commenting.CommentLineLength`                  | Standalone comment prose wrapped to 80 chars; premature wraps also fixed.   |
| `SineMacula.Commenting.ConsistentEnumCaseComments`         | Enum case docs are all-or-nothing within an enum.                           |
| `SineMacula.Commenting.MultilineMethodComment`             | A method's doc comment must span multiple lines.                            |
| `SineMacula.Commenting.RequireConstantComment`             | Every class/interface/enum/trait constant needs a doc comment.              |
| `SineMacula.Commenting.RequireCopyrightTag`                | Class/interface/enum/trait docblocks must carry an `@copyright` tag.        |
| `SineMacula.Commenting.RequireNonPromotedParameterComment` | Plain params mixed with promoted ones need a comment.                       |
| `SineMacula.Commenting.RequirePromotedPropertyComment`     | Every constructor-promoted property needs a doc comment.                    |
| `SineMacula.Commenting.SingleLineMemberComment`            | A property, constant or enum-case doc comment sits on one line.             |
| `SineMacula.Exceptions.DisallowBaseException`              | No throwing the base `\Exception`; throw a domain exception.                |
| `SineMacula.Exceptions.RequireEmptyCatchComment`           | An empty catch block must comment its intentional swallow.                  |
| `SineMacula.Functions.RequireSensitiveParameter`           | Secret-named params need `#[\SensitiveParameter]`.                          |
| `SineMacula.Metrics.MaxMethodCount`                        | A class/interface/trait/enum may declare at most 20 methods (tests exempt). |
| `SineMacula.Metrics.MethodLength`                          | A method body may have at most 50 significant lines (tests exempt).         |
| `SineMacula.Namespaces.RequireConcernsNamespace`           | Traits must live under a `Concerns` namespace segment.                      |
| `SineMacula.Namespaces.RequireContractsNamespace`          | Interfaces must live under a `Contracts` namespace segment.                 |
| `SineMacula.Namespaces.RequireEnumsNamespace`              | Enums must live under an `Enums` namespace segment.                         |
| `SineMacula.NamingConventions.BooleanMethodName`           | `bool` methods are predicates; command verbs/@imperative exempt.            |
| `SineMacula.NamingConventions.DisallowInterfacePrefix`     | Interface names must not use the Hungarian `I` prefix.                      |
| `SineMacula.NamingConventions.ValidEnumCaseName`           | Enum cases must be `SCREAMING_SNAKE_CASE`.                                  |
| `SineMacula.NamingConventions.ValidGlobalFunctionName`     | Global functions must be declared in `snake_case`.                          |
| `SineMacula.TypeHints.RequireConstantType`                 | Class/interface/enum/trait constants must declare a native type.            |
| `SineMacula.WhiteSpace.PromotedConstructorSpacing`         | Blank line above each promoted-constructor parameter.                       |

### PHPStan rules

| Identifier                         | Enforces                                                          |
|------------------------------------|-------------------------------------------------------------------|
| `sineMacula.mutableStaticProperty` | Static properties written at runtime; `@managed-static` opts out. |

### ESLint rules

All rules run in the base layer except `boolean-method-name`, which resolves return types and so requires the opt-in
type-checked layer.

| Rule                                           | Enforces                                                                            |
|------------------------------------------------|-------------------------------------------------------------------------------------|
| `@sinemacula/no-interface-prefix`              | Interface and type-alias names must not use the Hungarian `I` prefix.               |
| `@sinemacula/require-readonly-public-property` | Public class properties (declared or promoted) must be `readonly`.                  |
| `@sinemacula/valid-enum-member-name`           | Enum members must be declared in `SCREAMING_SNAKE_CASE`.                            |
| `@sinemacula/boolean-method-name`              | Boolean-returning methods need an is/has/can prefix; `@imperative` exempt.          |
| `@sinemacula/no-mutable-static`                | No mutable exported bindings or mutable `static` class fields; test code exempt.    |
| `@sinemacula/max-methods-per-class`            | A single class may declare at most 20 methods; test code exempt.                    |
| `@sinemacula/no-base-error`                    | Throw a domain-specific `Error` subclass, never the base `Error`; test code exempt. |
| `@sinemacula/require-copyright`                | Every file must carry a documentation comment with `@copyright` and `@author`.      |
| `@sinemacula/align-doc-tags`                   | `@author` and `@copyright` values line up at a single column; autofixable.          |
| `@sinemacula/single-line-property-doc`         | A data member's documentation comment sits on one line; autofixable.                |
| `@sinemacula/multiline-function-doc`           | A method's documentation comment spans multiple lines; autofixable.                 |
| `@sinemacula/comment-line-wrap`                | Standalone comment prose wrapped to 80 chars; premature wraps also fixed.           |

`boolean-method-name` takes `additionalPrefixes`, `additionalPredicates` and `additionalCommandVerbs` (string arrays)
to widen the accepted vocabulary from a consumer config. `max-methods-per-class` takes `max`, `no-base-error` takes
`allow`, and `require-copyright` takes `tags` to adjust their defaults. `align-doc-tags` takes `tags` and `column`,
the column counting from the `@`, so the default of 14 gives `@author` six spaces and `@copyright` three.
Together `single-line-property-doc` and `multiline-function-doc` set a member's comment shape by its kind: data
members (interface property signatures, enum members and data class fields) take one line, while methods, interface
method signatures and class fields holding a function take several. A data comment is never required, only held to
one line where present; a free function keeps the freedom of either shape.

`comment-line-wrap` takes `maxLength` (default 80) and is the syntax-only counterpart of the PHP
`SineMacula.Commenting.CommentLineLength` sniff. It fills standalone `//` runs and multi-line docblock prose greedily,
reporting an overflowing line and a prematurely wrapped line on their own footings and autofixing both. Markdown
headings, docblock tag lines, machine-parsed tool directives (`eslint`, `biome-ignore`, `@ts-`, `Stryker`, `c8`/`v8`/
`istanbul ignore`, `@vite-ignore` and the like), fenced code, an indented code or command block, a doc-tag whose
value opens a multi-line bracketed type (an `array{...}` shape, a `<...>` generic or a `\Closure(...)` signature),
tables, separators, a line whose overflow is a single unbreakable token such as a long name or URL, trailing comments
after code and compact single-line docblocks are left untouched.

The base layer also switches on a set of built-in rules: `@typescript-eslint/no-explicit-any`, `curly` (a brace on every
control statement, as PSR-12 already requires on the PHP side), `max-lines-per-function` (50 lines, test code exempt)
and `max-depth` (4), plus `eslint-plugin-jsdoc` rules that require a documentation comment
on every declared function, method, class, interface member and class field, forbid types in `@param`/`@returns` (the
tags themselves are welcome, types belong in the signature) and keep a blank line above every documentation block,
single-line blocks included. The type-checked layer adds `@typescript-eslint/explicit-module-boundary-types` and
`@typescript-eslint/only-throw-error`.

## Requirements

- PHP ^8.3 (Composer package)
- Node.js (npm package)

## Testing

```bash
composer test                # PHPUnit suite for the custom sniffs and PHPStan rule
composer test:coverage       # suite with Clover coverage output
composer test:mutation       # Infection mutation gate (min MSI 90)
composer test:mutation:full  # full mutation suite without thresholds
composer analyse             # PHPStan static analysis
composer check               # static analysis and lint via qlty
composer format              # format via qlty
composer smells              # duplication / complexity smells via qlty
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
