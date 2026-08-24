# Coding Standards

[![Latest Stable Version](https://img.shields.io/packagist/v/sinemacula/coding-standards.svg)](https://packagist.org/packages/sinemacula/coding-standards)
[![npm Version](https://img.shields.io/npm/v/@sinemacula/coding-standards.svg)](https://www.npmjs.com/package/@sinemacula/coding-standards)
[![Build Status](https://github.com/sinemacula/coding-standards/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/sinemacula/coding-standards/actions/workflows/tests.yml)
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

The npm package ships only the static configs (`js/`, `markdown/`, `yaml/`, `shell/`, `security/`, `swift/`). The PHP
autoloaded code lives in the Composer package.

### Qlty (Swift-side: SwiftLint, SwiftFormat)

Swift consumers do not need the Composer or npm package. Qlty fetches the shared Swift configs from this repository as
a pinned source and installs the native tools on macOS.

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
`phpstan.neon` only needs its paths:

```neon
parameters:
    paths:
        - src
        - tests
```

Do not set `level`. Analysis runs through qlty, whose phpstan driver passes `--level=9` on the command line, and a
command-line level overrides the config file outright - so a level set here does nothing except mislead whoever reads
it next.

The base config enables PHPStan's checked-exception analysis: every exception a method can throw must appear in its
`@throws` tag, except a configured set of programming-error and infrastructure exceptions that stay unchecked - the
`LogicException`, `RuntimeException` and `Error` families among them (see `php/phpstan-base.neon` for the full list).
Suppress a deliberate case with `@phpstan-ignore missingType.checkedException`.

#### Laravel projects

For Laravel projects, also install
[`sinemacula/coding-standards-laravel`](https://github.com/sinemacula/coding-standards-laravel) and reference its
`SineMaculaLaravel` PHPCS standard (which includes this one) in place of `SineMacula`. It adds the Laravel-specific
sniffs and PHPStan rules; see that package's README for setup.

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

`extends` paths are resolved through normal Node module lookup, so the package only needs to be installed (no path math
against `node_modules/` required). Project-specific `files.includes` and `files.excludes` stay in the consumer config.

### ESLint (JavaScript / TypeScript)

ESLint runs *alongside* Biome, not in place of it. Biome keeps owning formatting and the fast syntactic lint; ESLint
adds only the two things Biome structurally cannot express: this package's custom structural rules and the opt-in
type-aware rules (the curated typescript-eslint set plus the type-driven custom rules). Add the linter, the
typescript-eslint tooling, and this package to your dev dependencies:

```bash
npm install --save-dev eslint typescript typescript-eslint eslint-plugin-jsdoc yaml-eslint-parser \
    @sinemacula/coding-standards
```

`yaml-eslint-parser` is imported by the base layer for the YAML comment-width block, so it has to resolve even in a
repository with no YAML worth linting; without it the flat config fails to load at all.

The package exposes three flat-config entry points:

- `@sinemacula/coding-standards/js/eslint` - the base layer of syntax-only custom rules; needs no `tsconfig`, so it
  stays cheap and runs anywhere Biome runs. Covers `.ts`/`.js` and, for the comment-width rule alone, `.yml`/`.yaml`.
- `@sinemacula/coding-standards/js/eslint/type-checked` - the opt-in type-aware layer. It includes the base layer and
  adds the cross-file / type-driven rules, so it needs a consumer `tsconfig`; use it in place of the base layer where
  one exists.
- `@sinemacula/coding-standards/js/eslint/vue` - the opt-in Vue layer for single-file components. Unlike the type-aware
  layer it carries no base rules of its own, so spread it *alongside* whichever layer the repository already uses rather
  than in place of one.

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
`<script lang="ts">` blocks through the TypeScript parser, and holds component filenames to kebab-case. It also carries
the template layout rules, which is the one place ESLint takes on formatting: Biome does not understand single-file
components, so `.vue` markup would otherwise go unformatted entirely. Those rules are aligned to the shared four-space
indent.

When wiring ESLint through Qlty, the shared eslint plugin sandbox installs only `eslint`, `jest`, and `prettier` by
default, so the flat config's imports of this package and `typescript-eslint` fail to resolve. Widen the install filter
in your `.qlty/qlty.toml` so the sandbox carries them (this repository's `source.toml` exports the same override, but
source-exported plugin definitions do not reliably propagate, so mirror it consumer-side):

```toml
[plugins.definitions.eslint]
package_filters = [
    "@sinemacula/coding-standards", "typescript-eslint", "@typescript-eslint", "eslint-plugin-jsdoc",
    "yaml-eslint-parser",
]
```

Repositories enabling the Vue layer widen the same filter further, since its plugins have to resolve inside that sandbox
too:

```toml
[plugins.definitions.eslint]
package_filters = [
    "@sinemacula/coding-standards", "typescript-eslint", "@typescript-eslint", "eslint-plugin-jsdoc",
    "yaml-eslint-parser", "eslint-plugin-vue", "vue-eslint-parser", "eslint-plugin-check-file",
]
```

### TypeScript (tsconfig)

The package ships a shared `tsconfig` base so every TypeScript repository checks its code to the same bar. Install the
package (as above) and extend the base from your `tsconfig.json`:

```json
{
    "extends": "@sinemacula/coding-standards/js/tsconfig.base.json",
    "compilerOptions": {
        "lib": [
            "ES2023",
            "DOM",
            "DOM.Iterable"
        ],
        "types": [
            "node"
        ]
    },
    "include": [
        "src"
    ]
}
```

The base carries only the environment-independent options: the full strictness set and the module/resolution discipline.
Everything environment-specific stays in the consuming repo and layers on top - `lib` (DOM for the browser, none for a
Node service), `types`, Vue's `jsx`/`jsxImportSource`, `paths`, `noEmit`, and the `include`/`exclude` globs. The`target`
and `module` defaults suit bundler-built apps and libraries; a non-bundler project overrides them.

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

### Swift (SwiftLint and SwiftFormat)

Swift repositories consume the shared policy through Qlty. Enable the default source for the tools, this repository
for the exported configs, and both native plugins:

```toml
config_version = "0"

# SwiftLint's own `excluded:` list only applies when it walks a directory. Qlty
# passes explicit file paths, so generated and third-party sources have to be
# excluded here or the shared policy is reported against machine-written code.
exclude_patterns = [
    ".build/**",
    "build/**",
    "DerivedData/**",
    "Carthage/**",
    "Pods/**",
    "vendor/**",
    "**/Generated/**",
]

test_patterns = [
    "**/*Tests.swift",
    "**/Tests/**",
]

[[source]]
name = "default"
default = true

[[source]]
name = "sinemacula"
repository = "https://github.com/sinemacula/coding-standards"
tag = "<version>"

[[plugin]]
name = "swiftlint"

[[plugin]]
name = "swiftformat"
mode = "comment"
```

This is the same configuration the package's own integration test runs, so what is documented here is what CI exercises.

Run formatting and linting locally:

```bash
qlty fmt --all
qlty check --all
```

SwiftLint and SwiftFormat are native macOS plugins. Qlty Cloud's Linux workers cannot execute them, so every Swift
consumer needs a macOS CI job that verifies formatting and runs `qlty check`. Qlty Cloud still provides its built-in
Swift maintainability, duplication, complexity, security, and coverage capabilities.

The shared policy targets Swift 6 and deliberately contains no application-specific include paths or architecture
rules. Xcode compiler checks such as strict concurrency, warnings-as-errors, platform availability, and test builds
remain the consuming project's responsibility.

SwiftLint has no configuration inheritance. A `.swiftlint.yml` committed to a consuming repository *replaces* the
shared policy rather than extending it, so adding one to relax a single rule silently discards the whole standard.
Raise a pull request here instead, or copy the shared file wholesale and edit the copy.

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
| `js/eslint/` (YAML block)                 | ESLint               | Comment width in `.yml` / `.yaml`; yamllint owns rest  |
| `markdown/.markdownlint.json`             | markdownlint         | Markdown linting rules                                 |
| `yaml/.yamllint.yaml`                     | yamllint             | YAML linting rules                                     |
| `shell/.shellcheckrc`                     | ShellCheck           | Shell script linting rules                             |
| `security/.gitleaks.toml`                 | Gitleaks             | Secret-detection ruleset                               |
| `editorconfig/.editorconfig-checker.json` | editorconfig-checker | Disables only the max-line-length check                |
| `swift/.swiftlint.yml`                    | SwiftLint            | Shared Swift 6 lint, safety, concurrency, and metrics  |
| `swift/.swiftformat`                      | SwiftFormat          | Shared deterministic Swift 6 formatting policy         |

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
| `SineMacula.Functions.RequireSensitiveParameter`           | Secret-named params need `#[\SensitiveParameter]`; object types exempt.     |
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

| Identifier                             | Enforces                                                                       |
|----------------------------------------|--------------------------------------------------------------------------------|
| `sineMacula.mutableStaticProperty`     | Static properties written at runtime; `@managed-static` opts out.              |
| `sineMacula.readonlyClass`             | A final class with only readonly properties must be `readonly`.                |
| `sineMacula.redundantStaticReference`  | In a final class, `new static`, `static::` and `instanceof static` are `self`. |
| `sineMacula.redundantStaticReturnType` | In a final class, a `static` return type or `@return` must be `self`.          |

### ESLint rules

All rules run in the base layer except `boolean-method-name`, which resolves return types and so requires the opt-in
type-checked layer. Every rule is scoped to `.ts`/`.js`; `comment-line-wrap` alone also runs over `.yml`/`.yaml`.

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
| `@sinemacula/comment-line-wrap`                | Standalone comment prose wrapped to 80 chars, YAML included; premature wraps too.   |

`boolean-method-name` takes `additionalPrefixes`, `additionalPredicates` and `additionalCommandVerbs` (string arrays)
to widen the accepted vocabulary from a consumer config. `max-methods-per-class` takes `max`, `no-base-error` takes
`allow`, and `require-copyright` takes `tags` to adjust their defaults. `align-doc-tags` takes `tags` and `column`, the
column counting from the `@`, so the default of 14 gives `@author` six spaces and `@copyright` three. Together
`single-line-property-doc` and `multiline-function-doc` set a member's comment shape by its kind: data members
(interface property signatures, enum members and data class fields) take one line, while methods, interface method
signatures and class fields holding a function take several. A data comment is never required, only held to one line
where present; a free function keeps the freedom of either shape.

`comment-line-wrap` takes `maxLength` (default 80) and is the syntax-only counterpart of the PHP
`SineMacula.Commenting.CommentLineLength` sniff. It fills standalone `//` and `#` runs and multi-line docblock prose
greedily, reporting an overflowing line and a prematurely wrapped line on their own footings and autofixing both.
Markdown headings, docblock tag lines, machine-parsed tool directives (`eslint`, `biome-ignore`, `@ts-`, `Stryker`,
`c8`/`v8`/`istanbul ignore`, `@vite-ignore`, `yamllint`, `yaml-language-server`, `renovate:` and the like), fenced code,
an indented code or command block, a doc-tag whose value opens a multi-line bracketed type (an `array{...}` shape, a
`<...>` generic or a `\Closure(...)` signature), tables, separators, a line whose overflow is a single unbreakable token
such as a long name or URL, trailing comments after code and compact single-line docblocks are left untouched. Each
comment token reclaims its own width from the line, so a `#` comment fills one column further than a `//` one.

This is the one rule the base layer also carries over `.yml` and `.yaml`, which nothing else in the standards bounds for
comment width: yamllint's `line-length` cannot tell a comment from a value, so it would fault `run:` commands and action
refs nobody can shorten, and it has no autofix. The YAML block registers `yaml-eslint-parser` for its `#` comments and
enables this rule alone - none of `eslint-plugin-yml`'s own rules are switched on, so YAML quoting, key order and
indentation stay yamllint's business. Only standalone comments are reached: a block scalar's body is content rather
than comment, so a shell comment inside a `run: |` step is never seen, and a comment trailing a value is not standalone.

The base layer also switches on a set of built-in rules: `@typescript-eslint/no-explicit-any`, `curly` (a brace on every
control statement, as PSR-12 already requires on the PHP side), `max-lines-per-function` (50 lines, test code exempt)
and `max-depth` (4), plus `eslint-plugin-jsdoc` rules that require a documentation comment on every declared function,
method, class, interface member and class field, forbid types in `@param`/`@returns` (the tags themselves are welcome,
types belong in the signature) and keep a blank line above every documentation block, single-line blocks included. The
type-checked layer adds `@typescript-eslint/explicit-module-boundary-types` and
`@typescript-eslint/only-throw-error`.

### Swift policy

SwiftLint keeps its default rule set and adds curated opt-in rules with a strong correctness, concurrency, safety,
performance, or readability signal. The policy intentionally avoids analyzer-only rules, which need an Xcode compiler
log and cannot run through Qlty's normal lint driver.

Notable additions include checks for force-unwrapping, silently discarded throwing tasks, invalid concurrency
annotations, unsafe optional modelling, empty XCTest methods, unbalanced access control, inefficient collection
operations, oversized closures, and non-private SwiftUI state. A discarded throwing task is an error because it can
silently lose an operational failure; most style and maintainability findings retain warning severity.

The main review and hard ceilings are:

| Metric                | Warning | Error |
|-----------------------|--------:|------:|
| Line length           |     120 |   160 |
| File length           |     500 |   800 |
| Type body length      |     300 |   500 |
| Function body length  |      50 |    80 |
| Closure body length   |      50 |    80 |
| Cyclomatic complexity |      10 |    20 |
| Function parameters   |       6 |     8 |

SwiftFormat owns whitespace, wrapping, imports, and other mechanically correctable layout. Its configuration matches
SwiftLint on 120-column wrapping, import ordering, and no trailing commas, and it is the only tool of the two that
enforces four-space indentation.

The policy also carries over the documentation opinions the PHP and TypeScript standards enforce:

- `file_header` requires a copyright header, as `RequireCopyrightTagSniff` does for PHP classes and `require-copyright`
  does for TypeScript declarations. The pattern asserts only that the tag is present, so the holder, year and format
  stay the consuming project's choice, and SwiftFormat's `--header ignore` leaves an existing header untouched.
- `missing_docs` requires documentation on `open` and `public` declarations, the Swift equivalent of the PHP docblock
  sniffs and `jsdoc/require-jsdoc`. It is scoped to the public surface deliberately: demanding a comment on every
  internal member would generate noise the other two standards do not.
- `line_length` holds comments to the limit rather than exempting them. SwiftFormat wraps `//` comments to 120 but
  leaves `///` doc comments alone, so without this an over-long documentation line passes both tools - where PHP and
  TypeScript both wrap comment prose. Rules that can alter ownership,
control flow, explicit `Sendable` conformance, or public API shape are disabled; those changes require human review.

### Methods that only throw

A method that exists solely to refuse - a `__serialize()` that throws so a value holding a secret cannot reach a queue
payload or a cache entry - returns nothing on any path, and `never` is how to say so:

```php
/**
 * @throws \LogicException
 *
 * @return never
 */
public function __serialize(): never
{
    throw new LogicException('A token must not be serialised.');
}
```

`never` is a subtype of every return type, so narrowing to it always satisfies an inherited signature, a magic method's
expected return included. The one place it does not fit is a method a subclass is meant to return from, because a child
cannot widen `never` back. Such a method keeps the type it declares and throws anyway, which needs no directive:

```php
/**
 * @throws \LogicException
 *
 * @return array<int, string>
 */
public function build(): array
{
    throw new LogicException('Not implemented.');
}
```

Whether a documented return is ever produced is a question of control flow, not of tokens, so no sniff here asks it -
`Squiz.Commenting.FunctionComment.InvalidNoReturn` decides by looking for a `return` token and so faults exactly the
guard above. PHPStan's `return.missing` answers it properly: it reports a method that can reach its end without
returning the type it documents, and stays quiet where every path throws.

The one thing still worth knowing is that spelling out the contained type of a documented traversable can drag in
`mixed`, which the mixed ban faults on its own footing and which has its own directive.

## Requirements

- PHP ^8.3 (Composer package)
- Node.js (npm package)
- macOS and Qlty CLI (SwiftLint and SwiftFormat policy)

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
bash scripts/test-swift-policy.sh # exported Swift policy integration test (macOS)
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
