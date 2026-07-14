# Changelog

## [1.9.0](https://github.com/sinemacula/coding-standards/compare/v1.8.4...v1.9.0) (2026-07-14)


### Features

* **eslint:** add distributable ESLint custom-rule tier ([#87](https://github.com/sinemacula/coding-standards/issues/87)) ([695000e](https://github.com/sinemacula/coding-standards/commit/695000e244a8881c6b42cb7eeaa89df859c528ab))
* **eslint:** port a second batch of PHP rules to the ESLint tier ([#89](https://github.com/sinemacula/coding-standards/issues/89)) ([de9a0d5](https://github.com/sinemacula/coding-standards/commit/de9a0d52221ec58a9c56210ca5910de268b80439))

## [1.8.4](https://github.com/sinemacula/coding-standards/compare/v1.8.3...v1.8.4) (2026-06-25)


### Bug Fixes

* **phpcs:** honour docblock opt-out tags hidden behind attributes ([#85](https://github.com/sinemacula/coding-standards/issues/85)) ([5623663](https://github.com/sinemacula/coding-standards/commit/56236638a5cac998b50738c3c4183e0b07f8e2e8))

## [1.8.3](https://github.com/sinemacula/coding-standards/compare/v1.8.2...v1.8.3) (2026-06-25)


### Bug Fixes

* **phpcs:** match the method-signature length threshold to the 180 line limit ([#81](https://github.com/sinemacula/coding-standards/issues/81)) ([7097436](https://github.com/sinemacula/coding-standards/commit/7097436c9eea617eb707a66c6bff9f3aa72871f9))

## [1.8.2](https://github.com/sinemacula/coding-standards/compare/v1.8.1...v1.8.2) (2026-06-24)


### Bug Fixes

* re-release to verify the dependency-updates pipeline ([#79](https://github.com/sinemacula/coding-standards/issues/79)) ([e7a3a36](https://github.com/sinemacula/coding-standards/commit/e7a3a3656ca88c60ab9166cd80b042d75932f5f3))

## [1.8.1](https://github.com/sinemacula/coding-standards/compare/v1.8.0...v1.8.1) (2026-06-24)


### Bug Fixes

* **ci:** pin renovate action to a resolvable tag ([#75](https://github.com/sinemacula/coding-standards/issues/75)) ([96cd387](https://github.com/sinemacula/coding-standards/commit/96cd387468e7b600adfa6296d3628621aca05999))

## [1.8.0](https://github.com/sinemacula/coding-standards/compare/v1.7.2...v1.8.0) (2026-06-24)


### Features

* **ci:** self-hosted Renovate to auto-bump consumers on release ([#73](https://github.com/sinemacula/coding-standards/issues/73)) ([11845e5](https://github.com/sinemacula/coding-standards/commit/11845e55a0070804fbada3dd67c10a216f1a193c))

## [1.7.2](https://github.com/sinemacula/coding-standards/compare/v1.7.1...v1.7.2) (2026-06-23)


### Bug Fixes

* **qlty:** drop phpcs driver block from source.toml that broke all exports ([#71](https://github.com/sinemacula/coding-standards/issues/71)) ([660a74a](https://github.com/sinemacula/coding-standards/commit/660a74aacb57770dd8b04dd953c8e8711ec7b4f6))

## [1.7.1](https://github.com/sinemacula/coding-standards/compare/v1.7.0...v1.7.1) (2026-06-23)


### Bug Fixes

* **phpstan:** exempt test classes from sineMacula.mutableStaticProperty ([#69](https://github.com/sinemacula/coding-standards/issues/69)) ([6121a63](https://github.com/sinemacula/coding-standards/commit/6121a63f7f357346b36e32c53dcbb1320927f5b9))

## [1.7.0](https://github.com/sinemacula/coding-standards/compare/v1.6.0...v1.7.0) (2026-06-23)


### Features

* **phpcs:** documented-swallow empty-catch sniff ([#66](https://github.com/sinemacula/coding-standards/issues/66)) ([c026642](https://github.com/sinemacula/coding-standards/commit/c026642693ac19134394d940cab4c6014749cf01))
* **phpstan:** flag static properties only when written at runtime ([#63](https://github.com/sinemacula/coding-standards/issues/63)) ([11882cf](https://github.com/sinemacula/coding-standards/commit/11882cf22c944ad3e4a7b4029151eaa543d5e4a8))


### Bug Fixes

* **phpcs:** accept past-tense and idiomatic boolean method names ([#67](https://github.com/sinemacula/coding-standards/issues/67)) ([0e8d37f](https://github.com/sinemacula/coding-standards/commit/0e8d37f574b60842f427359ee62348853ed06a65))
* **phpcs:** allow snake_case data-object access (exclude MemberNotCamelCaps) ([#61](https://github.com/sinemacula/coding-standards/issues/61)) ([0e26903](https://github.com/sinemacula/coding-standards/commit/0e269036cc2d75c73a9dae042fdf8090e7ab46ca))
* **phpcs:** drop UnusedParameter (near-total false-positives) ([#62](https://github.com/sinemacula/coding-standards/issues/62)) ([a054fb6](https://github.com/sinemacula/coding-standards/commit/a054fb6c53f948e34b38f4c1b5484f39d1e7d113))
* **phpcs:** exclude InlineComment.DocBlock false-positives ([#60](https://github.com/sinemacula/coding-standards/issues/60)) ([c1b89e7](https://github.com/sinemacula/coding-standards/commit/c1b89e703e421ea5bb67d6ba3ff06822b5b77c75))
* **phpcs:** exempt test classes from RequireReadonlyPublicProperty ([#65](https://github.com/sinemacula/coding-standards/issues/65)) ([d1b0442](https://github.com/sinemacula/coding-standards/commit/d1b044273f23e61fcf8fc815f87aae23828948d6))
* **phpcs:** exempt test classes from RequireSensitiveParameter ([#68](https://github.com/sinemacula/coding-standards/issues/68)) ([6f33bb3](https://github.com/sinemacula/coding-standards/commit/6f33bb31165d138302617f777d96340d56bbb643))
* **phpcs:** raise cognitive complexity limit to 10 ([#64](https://github.com/sinemacula/coding-standards/issues/64)) ([1d23cad](https://github.com/sinemacula/coding-standards/commit/1d23cadce3745d94ca50f9e38c1ef24b19372cf7))
* **phpcs:** tolerate tool-directive comments and colliding FQCNs ([#58](https://github.com/sinemacula/coding-standards/issues/58)) ([4126863](https://github.com/sinemacula/coding-standards/commit/41268633294e70d9757edff1fccae7e01f639288))

## [1.6.0](https://github.com/sinemacula/coding-standards/compare/v1.5.1...v1.6.0) (2026-06-22)


### Features

* **phpcs:** blank-line padding for promoted constructors ([#57](https://github.com/sinemacula/coding-standards/issues/57)) ([dfe9488](https://github.com/sinemacula/coding-standards/commit/dfe948845694e1ee264956cfcfd6cf5ff94a3685))
* **phpstan:** [@managed-static](https://github.com/managed-static) opt-out for the mutable-static rule ([#55](https://github.com/sinemacula/coding-standards/issues/55)) ([3ee6af4](https://github.com/sinemacula/coding-standards/commit/3ee6af4bba039159344a8d295c538045da6a203a))


### Bug Fixes

* **phpcs:** skip RequireReadonlyPublicProperty in readonly classes ([#53](https://github.com/sinemacula/coding-standards/issues/53)) ([9c88811](https://github.com/sinemacula/coding-standards/commit/9c888119b9df89ca0564172d49bdf9e6a5248859))

## [1.5.1](https://github.com/sinemacula/coding-standards/compare/v1.5.0...v1.5.1) (2026-06-21)


### Bug Fixes

* **qlty:** accept phpcs 4.x exit code 3 so findings aren't dropped ([#51](https://github.com/sinemacula/coding-standards/issues/51)) ([df42e16](https://github.com/sinemacula/coding-standards/commit/df42e16d2b16f09e9d9ecaffec92c01fd2d69f17))

## [1.5.0](https://github.com/sinemacula/coding-standards/compare/v1.4.0...v1.5.0) (2026-06-21)


### Features

* **phpcs:** support PHP_CodeSniffer 4.x ([#49](https://github.com/sinemacula/coding-standards/issues/49)) ([f7ebff3](https://github.com/sinemacula/coding-standards/commit/f7ebff32328168da1152b9d2b350e7d62326f244))

## [1.4.0](https://github.com/sinemacula/coding-standards/compare/v1.3.1...v1.4.0) (2026-06-21)


### Features

* **phpcs:** allow handle() as a boolean method name ([#41](https://github.com/sinemacula/coding-standards/issues/41)) ([c9df7c9](https://github.com/sinemacula/coding-standards/commit/c9df7c9fadcf00f7bf89b78f9270b7fab2c71f72))
* **phpcs:** require traits to live in a Concerns namespace ([#42](https://github.com/sinemacula/coding-standards/issues/42)) ([7029c60](https://github.com/sinemacula/coding-standards/commit/7029c60798f861a899df7a9091fd84532a7120fb))

## [1.3.1](https://github.com/sinemacula/coding-standards/compare/v1.3.0...v1.3.1) (2026-06-20)


### Bug Fixes

* **phpcs:** stop forcing leading underscore on private methods ([#37](https://github.com/sinemacula/coding-standards/issues/37)) ([c7f3003](https://github.com/sinemacula/coding-standards/commit/c7f30035d0eada77846ecc749b5e464434bc785b))

## [1.3.0](https://github.com/sinemacula/coding-standards/compare/v1.2.0...v1.3.0) (2026-06-20)


### Features

* **cs-fixer:** enable multiline_promoted_properties ([#33](https://github.com/sinemacula/coding-standards/issues/33)) ([cc084dd](https://github.com/sinemacula/coding-standards/commit/cc084ddcb75f0fe1d5ca9b231124bcd76e0c8b3f))
* **phpcs:** 13 custom sniffs (naming, types, design, metrics, comments, exceptions) ([#30](https://github.com/sinemacula/coding-standards/issues/30)) ([99bf044](https://github.com/sinemacula/coding-standards/commit/99bf0447ae15ca366f985d93541c57d2407dd5e0))
* **phpcs:** add ValidEnumCaseName sniff for SCREAMING_SNAKE_CASE enum cases ([#29](https://github.com/sinemacula/coding-standards/issues/29)) ([b435d21](https://github.com/sinemacula/coding-standards/commit/b435d212d8f5dfa9819f2ac0f07ca1f96666f925))
* **php:** expand phpcs/Slevomat ruleset and enable strict types ([#27](https://github.com/sinemacula/coding-standards/issues/27)) ([e410dca](https://github.com/sinemacula/coding-standards/commit/e410dcaef8a06de7ccd7b6328591c19e117a244c))
* **phpstan:** rule harness, #[\Override] enforcement, no-mutable-static rule ([#32](https://github.com/sinemacula/coding-standards/issues/32)) ([2e02605](https://github.com/sinemacula/coding-standards/commit/2e026051efd663fb9b4519927b7030e756d2e708))
* review-items pass - predicate naming, disallow mixed, readonly public properties ([#34](https://github.com/sinemacula/coding-standards/issues/34)) ([af2cbc1](https://github.com/sinemacula/coding-standards/commit/af2cbc15d6e7375aeb3895c0d55b8e63cb23aad4))

## [1.2.0](https://github.com/sinemacula/coding-standards/compare/v1.1.3...v1.2.0) (2026-06-16)


### Features

* **markdownlint:** exclude generated CHANGELOG.md from linting ([#25](https://github.com/sinemacula/coding-standards/issues/25)) ([89b95e7](https://github.com/sinemacula/coding-standards/commit/89b95e7e463fcc1539265aedfaa7e0e8b1918b6d))
