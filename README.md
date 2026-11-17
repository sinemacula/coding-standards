# Coding Standards

[![Latest Stable Version](https://img.shields.io/packagist/v/sinemacula/coding-standards.svg)](https://packagist.org/packages/sinemacula/coding-standards)
[![npm Version](https://img.shields.io/npm/v/@sinemacula/coding-standards.svg)](https://www.npmjs.com/package/@sinemacula/coding-standards)
[![Build Status](https://github.com/sinemacula/coding-standards/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/sinemacula/coding-standards/actions/workflows/tests.yml)
[![License](https://img.shields.io/github/license/sinemacula/coding-standards.svg)](LICENSE)

Shared coding standards, static analysis configs and the Qlty source for Sine Macula repositories.

The packages ship configuration only, with no runtime dependencies. Consuming projects install the tools themselves.

## Installation

### Composer (PHP CS Fixer, PHPCS, PHPStan)

```bash
composer require --dev sinemacula/coding-standards
```

### npm (Biome, ESLint, tsconfig)

```bash
npm install --save-dev @sinemacula/coding-standards
```

The npm package ships only the static configs (`js/`, `markdown/`, `yaml/`, `shell/`, `security/`, `swift/`,
`terraform/`). The PHP code ships in the Composer package.

### Qlty (Swift, Terraform)

Swift and Terraform consumers need neither package. The Qlty source supplies the shared configs and pins the tools.

## Usage

Each consuming project adds thin wrapper files at its root that reference the shared configs.

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

Pass rule overrides as a second argument:

```php
return PhpCsFixerConfig::make(
    [__DIR__ . '/src', __DIR__ . '/tests'],
    ['strict_comparison' => false],
);
```

Give the qlty plugin this package for its tool sandbox, and do **not** set `package_file` or `package_filters` on it:

```toml
[[plugin]]
name = "php-cs-fixer"
extra_packages = ["sinemacula/coding-standards@1.23.0"]
```

`extra_packages` installs into the tool sandbox only, so it does not touch your dependency graph. Setting
`package_file` makes qlty ignore `extra_packages` silently. Use an exact version: the shared Renovate preset resolves a
caret range to the newest release it already allows, so a range is reported as current and never raised. The entry
lives in your own file because the source, consumed at a tag, cannot pin the package it is published with.

The shared source runs qlty on PHP 8.5. A project that analyses on another PHP version sets its own runtime, and qlty
resolves each tool sandbox for it:

```toml
[runtimes.enabled]
php = "8.3"
```

### PHPCS

The `SineMacula` standard is auto-discovered through the `phpcodesniffer-standard` Composer type. Create a `phpcs.xml`
at your project root:

```xml
<?xml version="1.0"?>
<ruleset name="Project">
    <rule ref="SineMacula"/>
    <file>src</file>
    <file>tests</file>
</ruleset>
```

### PHPStan

The base config is auto-included through `extra.phpstan.includes` in `composer.json`. Your project's `phpstan.neon`
only needs its paths:

```neon
parameters:
    paths:
        - src
        - tests
```

Point the qlty plugin at the Composer file that holds your analysis stack, and leave its `version` to the source:

```toml
[[plugin]]
name = "phpstan"
package_file = "composer.json"
```

Do not set `level`. qlty's phpstan driver passes `--level=9` on the command line, which overrides the config file.

The base config enables checked-exception analysis: every exception a method can throw must appear in its `@throws`
tag, except the programming-error and infrastructure exceptions listed in `php/phpstan-base.neon` (the
`LogicException`, `RuntimeException` and `Error` families among them). Suppress a deliberate case with
`@phpstan-ignore missingType.checkedException`. The PHPCS standard asks for a `@throws` tag for each exception thrown in
a method's body, checked or not, and accepts a tag for an exception a callee propagates; PHPStan's `tooWideThrowType`
reports a tag for an exception nothing can throw.

`argument.type` is reported. The shared PHP CS Fixer rules declare `strict_types`, so an argument of the wrong type is
a `TypeError` at runtime. Narrow the value where it is read (`$request->integer('limit')`, `Config::integer(...)`, a
null check) or cast it explicitly where the conversion is intended.

#### Laravel projects

Also install [`sinemacula/coding-standards-laravel`](https://github.com/sinemacula/coding-standards-laravel) and
reference its `SineMaculaLaravel` PHPCS standard, which includes this one, in place of `SineMacula`. It adds the
Laravel-specific sniffs and PHPStan rules; see that package's README for setup.

### Biome (JavaScript / TypeScript)

Through Qlty, the source stages the shared config at the repository root as `biome.shared.json` while Biome runs.
Enable Biome 2.2.0 or later in your `.qlty/qlty.toml`:

```toml
[[plugin]]
name = "biome"
version = "2.5.6"
```

Then create `.qlty/configs/biome.json`, which extends the shared config by that relative path:

```json
{
    "$schema": "https://biomejs.dev/schemas/2.5.6/schema.json",
    "root": true,
    "extends": [
        "./biome.shared.json"
    ],
    "files": {
        "includes": [
            "**",
            "!**/node_modules/**",
            "!**/vendor/**"
        ]
    }
}
```

The project config is required; without one, Biome runs on its own defaults. Settings in it apply over the shared
config, so project-specific `files.includes` and overrides go there. A project config that still extends
`js/biome.json` must switch to `./biome.shared.json`. Leave the Biome plugin definition and its drivers as the source
sets them: a `--config-path` or `exported_config_paths` override breaks the staging. Biome before 2.2.0 resolves the
relative `extends` from the staged file's real location and fails to load it.

Without Qlty, install the npm package and extend `@sinemacula/coding-standards/js/biome.shared.json` from the
project's `biome.json`. The shared config leaves Biome's VCS integration off, because Qlty formats a copy of each file
outside the repository; a project that runs Biome directly can turn it on in its own `biome.json`.

### ESLint (JavaScript / TypeScript)

ESLint runs alongside Biome. Biome owns formatting and the syntactic lint; ESLint adds this package's custom
structural rules and the opt-in type-aware rules. Install:

```bash
npm install --save-dev eslint typescript typescript-eslint eslint-plugin-jsdoc yaml-eslint-parser \
    @sinemacula/coding-standards
```

`yaml-eslint-parser` is imported by the base layer, so the config fails to load without it, even in a repository with
no YAML.

The package exposes three flat-config entry points:

- `@sinemacula/coding-standards/js/eslint` - the base layer of syntax-only rules. Needs no `tsconfig`. Covers
  `.ts`/`.js` and, for the comment-width rule alone, `.yml`/`.yaml`.
- `@sinemacula/coding-standards/js/eslint/type-checked` - the base layer plus the type-aware rules. Needs a
  `tsconfig`; use it in place of the base layer where one exists.
- `@sinemacula/coding-standards/js/eslint/vue` - the Vue single-file-component layer. It carries no base rules, so
  spread it after whichever layer the repository uses.

Create an `eslint.config.js` (or `.qlty/configs/eslint.config.mjs` when wired through Qlty) that spreads the layer
you want. Without a `tsconfig`:

```js
import sm from '@sinemacula/coding-standards/js/eslint';

export default [...sm];
```

With a `tsconfig`:

```js
import typeChecked from '@sinemacula/coding-standards/js/eslint/type-checked';

export default [...typeChecked];
```

Vue repositories add the Vue toolchain and spread the Vue layer after their existing one:

```bash
npm install --save-dev eslint-plugin-vue vue-eslint-parser eslint-plugin-check-file
```

```js
import typeChecked from '@sinemacula/coding-standards/js/eslint/type-checked';
import vue from '@sinemacula/coding-standards/js/eslint/vue';

export default [...typeChecked, ...vue];
```

The Vue layer registers the single-file-component parser, resolves `<script lang="ts">` through the TypeScript
parser, and requires kebab-case component filenames. It also carries the template layout rules, set to a four-space
indent, because Biome cannot format `.vue` markup.

Through Qlty, the eslint plugin sandbox installs only `eslint`, `jest` and `prettier` by default, so the config's
imports fail to resolve. Widen the install filter in your `.qlty/qlty.toml`:

```toml
[plugins.definitions.eslint]
package_filters = [
    "@sinemacula/coding-standards", "typescript-eslint", "@typescript-eslint", "eslint-plugin-jsdoc",
    "yaml-eslint-parser",
]
```

With the Vue layer, add its packages too:

```toml
[plugins.definitions.eslint]
package_filters = [
    "@sinemacula/coding-standards", "typescript-eslint", "@typescript-eslint", "eslint-plugin-jsdoc",
    "yaml-eslint-parser", "eslint-plugin-vue", "vue-eslint-parser", "eslint-plugin-check-file",
]
```

### TypeScript (tsconfig)

Extend the shared base from your `tsconfig.json`:

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

The base carries the strictness and module-resolution options only. Environment-specific settings stay in the
consuming repository: `lib`, `types`, Vue's `jsx`/`jsxImportSource`, `paths`, `noEmit` and `include`/`exclude`. The
`target` and `module` defaults suit bundler-built code; a non-bundler project overrides them.

The base sets `noPropertyAccessFromIndexSignature`, so index-signature properties are read with brackets
(`config['key']`). Biome cannot see types, so its `useLiteralKeys` rule is off and the type-checked ESLint layer's
`@typescript-eslint/dot-notation` enforces dot access for declared properties.

### Knip (JavaScript / TypeScript)

Knip needs no shared config; its defaults find entry points from `package.json` and its plugins. Enable it in
`.qlty/qlty.toml`:

```toml
[[plugin]]
name = "knip"
```

Add a `knip.json` at the project root only for what Knip cannot find on its own, such as an entry point outside its
defaults. Knip has no `extends`, so that file holds the project's whole Knip config.

### Qlty

Reference this repository as a source in your project's `.qlty/qlty.toml`, pinning `tag` to the latest
[release](https://github.com/sinemacula/coding-standards/releases):

```toml
[[source]]
name = "sinemacula"
repository = "https://github.com/sinemacula/coding-standards"
tag = "<version>"
```

The source enables these plugins at the versions and modes every project runs: TruffleHog, markdownlint, yamllint,
actionlint, OSV-Scanner, Trivy (misconfiguration and dependency scanning), editorconfig-checker, PHPCS, PHP CS Fixer,
PHPStan, Radarlint, Terraform (formatting only) and TFLint. A plugin with nothing to lint in a repository does not run
there. The source also sets the PHP runtime and shared excludes, stops qlty discarding PHPCS 4.x findings as a crash,
and supplies the ESLint and Biome wiring for projects that enable them.

List a plugin in your own file only to change it. A version or mode you leave out is taken from the source, but a list
you set, such as `drivers` or `extra_packages`, replaces the source's. To turn a plugin off, set `mode = "disabled"`.

The source sets the smell policy (see `source.toml`): smells run in `comment` mode, the parameter-count check is off,
and the return-statement, function-complexity and file-complexity thresholds are raised. Leave `[smells]` out of your
own `.qlty/qlty.toml` to inherit it. To change a threshold, set it there; to re-enable a check the source switches off,
also set `enabled = true`, because the two files are merged.

The source ignores three Radarlint rules through `[[triage]]`:

- `php:S100` (function naming) wants camelCase everywhere, which conflicts with the snake_case global functions
  `SineMacula.NamingConventions.ValidGlobalFunctionName` requires. PSR1 already covers methods.
- `php:S1144` (unused private method) cannot see a call from a used trait or a first-class callable such as
  `self::method(...)`. PHPStan reports private methods nothing calls.
- `php:S1172` (unused parameter) flags the positional parameters a framework callback must declare to reach a later
  one, such as `$attribute` in a validation closure taking `($attribute, $value, $fail)`.

Your own `[[triage]]` entries are added alongside the source's.

### Swift (SwiftLint and SwiftFormat)

Swift repositories consume the shared policy through Qlty. Enable the default source for the tools, this repository
for the configs, and both native plugins:

```toml
config_version = "0"

# SwiftLint's own `excluded:` list only applies when it walks a directory. Qlty
# passes explicit file paths, so generated and third-party sources are excluded
# here.
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

This is the configuration the package's integration test runs. Run formatting and linting locally:

```bash
qlty fmt --all
qlty check --all
```

SwiftLint and SwiftFormat are native macOS plugins that Qlty Cloud's Linux workers cannot run, so every Swift consumer
needs a macOS CI job that verifies formatting and runs `qlty check`. Qlty Cloud still provides its built-in Swift
maintainability, duplication, complexity, security and coverage analysis.

The policy targets Swift 6 and holds no application-specific paths or architecture rules. Compiler checks such as
strict concurrency, warnings-as-errors, platform availability and test builds stay with the consuming project.

SwiftLint has no configuration inheritance: a `.swiftlint.yml` in a consuming repository replaces the shared policy
entirely. Raise a pull request here instead, or copy the shared file wholesale and edit the copy.

### Terraform (Terraform and TFLint)

The Qlty source runs `terraform fmt` and TFLint at pinned versions with the shared `.tflint.hcl`. Keep
`.terraform-version` at the pinned Terraform release. To change the version, give the `terraform` plugin a `version` in
your own `.qlty/qlty.toml` and repeat `drivers = ["format"]`; an entry without drivers runs all of them, including a
validate driver that cannot pass without `terraform init`.

Validate in CI with the reusable workflow, which initialises without a backend and runs `terraform validate`:

```yaml
jobs:
    terraform:
        uses: sinemacula/.github/.github/workflows/terraform-tests.yml@<version>
        permissions:
            contents: read
```

`terraform fmt` indents with two spaces. Add a section for it to your `.editorconfig`:

```ini
[*.{tf,tfvars,hcl}]
indent_size = 2
```

A `.tflint.hcl` in a consuming repository replaces the shared policy. Raise a pull request here instead.

## What's Included

| Path                                      | Tool                 | Description                                            |
|-------------------------------------------|----------------------|--------------------------------------------------------|
| `src/PhpCsFixerConfig.php`                | PHP CS Fixer         | Factory class for building PHP CS Fixer configurations |
| `php/.php-cs-fixer.rules.php`             | PHP CS Fixer         | Shared rules array (PSR-12 base + org conventions)     |
| `SineMacula/ruleset.xml`                  | PHPCS                | Auto-discovered coding standard (PSR-12 + exclusions)  |
| `php/phpstan-base.neon`                   | PHPStan              | Base config (org-wide ignored errors + settings)       |
| `js/biome.shared.json`                    | Biome                | JavaScript / TypeScript formatter + linter rules       |
| `js/eslint/`                              | ESLint               | Structural, type-aware + Vue rules; runs with Biome    |
| `js/eslint/` (YAML block)                 | ESLint               | Comment width in `.yml` / `.yaml`; yamllint owns rest  |
| `js/tsconfig.base.json`                   | TypeScript           | Shared strictness and module-resolution options        |
| `markdown/.markdownlint.json`             | markdownlint         | Markdown linting rules                                 |
| `yaml/.yamllint.yaml`                     | yamllint             | YAML linting rules                                     |
| `shell/.shellcheckrc`                     | ShellCheck           | Shell script linting rules                             |
| `security/.gitleaks.toml`                 | Gitleaks             | Secret-detection ruleset                               |
| `security/trivy.yaml`                     | Trivy                | Skips generated and installed trees when scanning      |
| `security/zizmor.yml`                     | zizmor               | SHA-pins third-party actions; two noisy audits off     |
| `editorconfig/.editorconfig-checker.json` | editorconfig-checker | Disables only the max-line-length check                |
| `swift/.swiftlint.yml`                    | SwiftLint            | Shared Swift 6 lint, safety, concurrency, and metrics  |
| `swift/.swiftformat`                      | SwiftFormat          | Shared deterministic Swift 6 formatting policy         |
| `terraform/.tflint.hcl`                   | TFLint               | Bundled Terraform ruleset at the `all` preset          |

## Rules

The custom rules this package enforces on top of PSR-12. Bypass a deliberate exception with the native directive:
`// phpcs:ignore <code>` for a sniff, `@phpstan-ignore <identifier>` for a PHPStan rule,
`// eslint-disable-next-line <rule>` for an ESLint rule.

### PHPCS sniffs

| Sniff                                                      | Enforces                                                                    |
|------------------------------------------------------------|-----------------------------------------------------------------------------|
| `SineMacula.Attributes.DisallowToolingAttribute`           | No IDE/tooling attributes (e.g. `JetBrains\PhpStorm`).                      |
| `SineMacula.Classes.RequireCombinedTraitImport`            | Traits must be imported in one `use` statement, not one per line.           |
| `SineMacula.Classes.RequireFinalClass`                     | Concrete classes must be `final` or `abstract` (`@inheritable` opts out).   |
| `SineMacula.Classes.RequireReadonlyPublicProperty`         | Public properties (declared or promoted) must be `readonly`.                |
| `SineMacula.Commenting.CommentLineLength`                  | Standalone comment prose wrapped to 80 chars; premature wraps also fixed.   |
| `SineMacula.Commenting.ConsistentEnumCaseComments`         | Enum case docs are all-or-nothing within an enum.                           |
| `SineMacula.Commenting.DisallowTagDescription`             | `@param` and `@throws` tags carry no description; autofixable.              |
| `SineMacula.Commenting.MultilineMethodComment`             | A method's doc comment must span multiple lines.                            |
| `SineMacula.Commenting.RequireConstantComment`             | Every class/interface/enum/trait constant needs a doc comment.              |
| `SineMacula.Commenting.RequireCopyrightTag`                | Class/interface/enum/trait docblocks must carry an `@copyright` tag.        |
| `SineMacula.Commenting.RequireNonPromotedParameterComment` | Plain params mixed with promoted ones need a comment.                       |
| `SineMacula.Commenting.RequireParamTag`                    | Each parameter needs an `@param` tag naming it; the type may span lines.    |
| `SineMacula.Commenting.RequirePromotedPropertyComment`     | Every constructor-promoted property needs a doc comment.                    |
| `SineMacula.Commenting.RequireThrowsTag`                   | Each exception thrown in a function body needs its own `@throws` tag.       |
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
| `SineMacula.Operators.DisallowCoalesceAfterConcat`         | No `??` after a bare concatenation, whose fallback can never run.           |
| `SineMacula.TypeHints.RequireConstantType`                 | Class/interface/enum/trait constants must declare a native type.            |
| `SineMacula.WhiteSpace.PromotedConstructorSpacing`         | Blank line above each promoted-constructor parameter.                       |

`DisallowTagDescription` exists because the function comment sniff and PHP CS Fixer disagree on a tag description's
case and full stop. A bare tag, `@param  int  $count` or `@throws \RuntimeException`, satisfies both, so the fixer
removes the description, continuation lines included.

`RequireParamTag` takes over the function comment sniff's `@param` variable checks, under the same codes and messages,
because that sniff reads only a tag's first line and loses the variable of a type that spans lines. A `// phpcs:ignore`
for those codes names `SineMacula.Commenting.RequireParamTag` in place of `Squiz.Commenting.FunctionComment`. It also
reads a literal type holding a dot (`'a.b'`, `1.5`) and a `$this` type correctly. PHP CS Fixer adds a second `@param`
for a callable type that names its own parameters, so leave a callable type's parameters unnamed.

Slevomat's fully qualified annotation sniff treats a local type alias used inside another alias's definition as a
class: in `@phpstan-type Rows list<Row>` it reports `Row` and its fixer rewrites it to `\App\Row`. The same happens to
an alias brought in with `@phpstan-import-type`, and a suppression inside the docblock does not reach it. Until a fixed
release ships, write a local alias's shape out in full (`@phpstan-type Rows list<array{id: string}>`) and use an
imported alias directly in `@param`, `@return` or `@var`. See [REPORTS.md](REPORTS.md).

Two sniffs take properties that exempt framework code whose names and shapes are fixed by convention:

- `BooleanMethodName` takes `ignoredClassSuffixes`. Methods of a named class, interface, trait or enum ending in one
  are not checked, which suits a Laravel policy, whose ability methods (`view`, `update`, `before`) the Gate resolves
  by name. An anonymous class is never exempt.
- `RequireReadonlyPublicProperty` takes `ignoredParentClasses`, whose subclasses are exempt, and
  `ignoredInterfaceProperties`, which maps an interface to a comma-separated list of property names exempt in any
  class whose own `implements` clause names it, such as a queued job's `tries` and `timeout`.
  `ignoredParentClassProperties` does the same for the parent a class's own `extends` clause names, for jobs that
  extend a base implementing the contract; a class matching both gets both lists. Only the listed properties are
  exempt. Parents and interfaces match case-insensitively on their short name, so `ShouldQueue` and
  `\Illuminate\Contracts\Queue\ShouldQueue` both match whether the clause imports the name or writes it fully
  qualified; an import aliased with `as` matches only its alias.

```xml
<rule ref="SineMacula.NamingConventions.BooleanMethodName">
    <properties>
        <property name="ignoredClassSuffixes" type="array">
            <element value="Policy"/>
        </property>
    </properties>
</rule>
<rule ref="SineMacula.Classes.RequireReadonlyPublicProperty">
    <properties>
        <property name="ignoredInterfaceProperties" type="array">
            <element key="ShouldQueue" value="tries,timeout,backoff,maxExceptions,queue,connection,delay"/>
        </property>
        <property name="ignoredParentClassProperties" type="array">
            <element key="Job" value="tries,timeout,backoff,maxExceptions,queue,connection,delay"/>
        </property>
    </properties>
</rule>
```

### PHPStan rules

| Identifier                             | Enforces                                                                       |
|----------------------------------------|--------------------------------------------------------------------------------|
| `sineMacula.mutableStaticProperty`     | Runtime-written static properties; `@managed-static` opts out; tests exempt.   |
| `sineMacula.readonlyClass`             | A final class with only readonly properties must be `readonly`.                |
| `sineMacula.redundantStaticReference`  | In a final class, `new static`, `static::` and `instanceof static` are `self`. |
| `sineMacula.redundantStaticReturnType` | In a final class, a `static` return type or `@return` must be `self`.          |

### ESLint rules

All rules run in the base layer except `boolean-method-name`, which resolves return types and so needs the
type-checked layer. `comment-line-wrap` alone also runs over `.yml`/`.yaml`.

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
| `@sinemacula/require-file-description`         | The same comment must open with a prose summary above those tags.                   |
| `@sinemacula/align-doc-tags`                   | `@author` and `@copyright` values line up at a single column; autofixable.          |
| `@sinemacula/single-line-property-doc`         | A data member's documentation comment sits on one line; autofixable.                |
| `@sinemacula/multiline-function-doc`           | A method's documentation comment spans multiple lines; autofixable.                 |
| `@sinemacula/comment-line-wrap`                | Standalone comment prose wrapped to 80 chars, YAML included; premature wraps too.   |

Options:

- `boolean-method-name`: `additionalPrefixes`, `additionalPredicates`, `additionalCommandVerbs` (string arrays).
- `require-readonly-public-property`: `ignoredParentClasses`.
- `max-methods-per-class`: `max`.
- `no-base-error`: `allow`.
- `require-copyright`, `require-file-description`: `tags`. A project that narrows one should narrow the other to match.
- `align-doc-tags`: `tags` and `column`, counted from the `@`; the default of 14 gives `@author` six spaces and
  `@copyright` three.
- `comment-line-wrap`: `maxLength` (default 80).

`single-line-property-doc` and `multiline-function-doc` set a member's comment shape by its kind. Data members
(interface property signatures, enum members, data class fields) take one line; methods, interface method signatures
and class fields holding a function take several. A data comment is not required, only held to one line where present.
A free function may use either shape.

`require-copyright` and `require-file-description` split the file header between them. Both find the header by the
tags it carries, so a module documented at its export below the imports counts. Only the block's first non-blank line
is read, to check it is prose and not a tag. A file with no header at all is reported by `require-copyright` alone.

`comment-line-wrap` is the counterpart of the `SineMacula.Commenting.CommentLineLength` sniff. It fills standalone
`//` and `#` runs and multi-line docblock prose greedily, reporting and autofixing both overflowing and prematurely
wrapped lines. It leaves alone: Markdown headings, docblock tag lines, tool directives (`eslint`, `biome-ignore`,
`@ts-`, `Stryker`, `c8`/`v8`/`istanbul ignore`, `@vite-ignore`, `yamllint`, `yaml-language-server`, `renovate:` and
the like), fenced code, indented code or command blocks, a doc tag whose value opens a multi-line bracketed type
(`array{...}`, `<...>`, `\Closure(...)`), tables, separators, a line whose overflow is a single unbreakable token,
trailing comments after code and compact single-line docblocks. A `#` comment fills one column further than a `//` one.
Unlike the PHP sniff, it does not start a new paragraph at a `//` line opening with a capital letter, so a title or
one-line note can be merged into the sentence below.

Over YAML, `comment-line-wrap` is the only rule enabled, because yamllint's `line-length` cannot tell a comment from a
value and has no autofix. Only standalone comments are reached: a comment inside a block scalar such as `run: |` is
content, and a comment trailing a value is not standalone. YAML quoting, key order and indentation stay with yamllint.

The base layer also enables `@typescript-eslint/no-explicit-any`, `curly` (`all`), `max-lines-per-function` (50) and
`max-depth` (4), plus `eslint-plugin-jsdoc` rules requiring a documentation comment on every declared function,
method, class, interface member and class field, a description on every `@param` and `@returns`, and a blank line
above every documentation block. `jsdoc/no-types` runs over TypeScript files only, where the signature already holds
the type. The type-checked layer adds `@typescript-eslint/await-thenable`, `consistent-type-imports`, `dot-notation`
(with `allowIndexSignaturePropertyAccess`), `explicit-module-boundary-types`, `no-floating-promises`,
`no-misused-promises` and `only-throw-error`.

Test files (`*.test.*`, `*.spec.*`, and anything under `__tests__/`, `tests/` or `test-support/`) are exempt from
`max-lines-per-function` and `max-depth` only. Their helpers, factories and builders still need documentation; a test
case passed as an arrow to `it(...)` does not.

### Swift policy

SwiftLint keeps its default rule set and adds opt-in rules for correctness, concurrency, safety, performance and
readability. Analyzer-only rules are left out, because they need an Xcode compiler log that Qlty's lint driver cannot
supply.

Additions include checks for force-unwrapping, discarded throwing tasks, invalid concurrency annotations, unsafe
optional modelling, empty XCTest methods, unbalanced access control, inefficient collection operations, oversized
closures and non-private SwiftUI state. A discarded throwing task is an error, because it can lose a failure; most
other findings are warnings.

| Metric                | Warning | Error |
|-----------------------|--------:|------:|
| Line length           |     120 |   160 |
| File length           |     500 |   800 |
| Type body length      |     300 |   500 |
| Function body length  |      50 |    80 |
| Closure body length   |      50 |    80 |
| Cyclomatic complexity |      10 |    20 |
| Function parameters   |       6 |     8 |

SwiftFormat owns whitespace, wrapping, imports and other mechanically correctable layout. It matches SwiftLint on
120-column wrapping, import ordering and no trailing commas, and alone enforces four-space indentation. Formatter rules
that can alter ownership, control flow, explicit `Sendable` conformance or public API shape are disabled.

The policy carries over the documentation rules of the PHP and TypeScript standards:

- `file_header` requires a copyright header. It checks only that the tag is present, so the holder, year and format
  are the project's choice, and SwiftFormat's `--header ignore` leaves an existing header alone.
- `missing_docs` requires documentation on `open` and `public` declarations.
- `line_length` applies to comments. SwiftFormat wraps `//` comments at 120 but leaves `///` doc comments alone, so
  this is what bounds them.

### Methods that only throw

A method that exists only to refuse, such as a `__serialize()` that throws so a value holding a secret cannot reach a
queue payload or cache, returns nothing on any path. Declare it `never`:

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

`never` is a subtype of every return type, so it always satisfies an inherited signature, magic methods included. The
exception is a method a subclass is meant to return from, because a child cannot widen `never`. Keep the declared type
and throw anyway; no directive is needed:

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

`Squiz.Commenting.FunctionComment.InvalidNoReturn` is excluded because it looks for a `return` token and so faults the
guard above. PHPStan's `return.missing` reports a method that can reach its end without returning its documented type.

Spelling out the contained type of a documented traversable can introduce `mixed`, which the mixed-type ban reports.
In a function's docblock, suppress it with `@phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint`: a
`// phpcs:ignore` line cannot reach a type further down a multi-line docblock, and above the signature it detaches the
docblock. A property or constant's single-line docblock does not allow that tag, so put the `// phpcs:ignore` line
directly above the docblock.

## Requirements

- PHP ^8.3 (Composer package)
- Node.js (npm package)
- macOS and Qlty CLI (SwiftLint and SwiftFormat policy)

## Testing

```bash
composer test                # PHPUnit suite for the custom sniffs and PHPStan rules
composer test:coverage       # suite with Clover coverage output
composer test:mutation       # Infection mutation gate on the diff (min MSI 90)
composer test:mutation:full  # full mutation suite without thresholds
composer analyse             # PHPStan static analysis
composer check               # static analysis and lint via qlty
composer format              # format via qlty
composer smells              # duplication / complexity smells via qlty
npm run test:js              # Vitest suite for the ESLint rules
npm run lint:js              # ESLint over this package's own rules
bash scripts/test-<tool>-policy.sh  # exported policy integration tests (knip, shared, swift, terraform, trivy, zizmor)
```

The Swift policy test needs macOS.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

See [SECURITY.md](SECURITY.md) for how to report a vulnerability.

## License

Licensed under the [Apache License, Version 2.0](https://www.apache.org/licenses/LICENSE-2.0).
