# Contributing

Contributions are welcome via GitHub pull requests. This guide covers the expectations for working on this package.

## Requirements

- PHP 8.3+
- Composer 2
- Node.js is only needed when working on the JavaScript, Markdown, YAML or shell configs distributed via npm

## Getting Started

```bash
git clone git@github.com:sinemacula/coding-standards.git
cd coding-standards
composer install
```

## Development Workflow

### Branching

Branch from `master` using the appropriate prefix:

| Prefix      | Purpose                          |
|-------------|----------------------------------|
| `feature/`  | New functionality                |
| `bugfix/`   | Bug fixes                        |
| `hotfix/`   | Urgent production fixes          |
| `refactor/` | Refactoring without new features |
| `chore/`    | Tooling, CI, dependencies        |

### Commits

This project uses [Conventional Commits](https://www.conventionalcommits.org/). Prefix your commit messages accordingly:

```text
feat(phpcs): add sniff for redundant else branches
feat(phpstan): enforce the #[\Override] attribute
fix(cs-fixer): correct promoted-property formatting
chore: update qlty configuration
```

### Code Quality

All code must pass static analysis before submission:

```bash
composer check    # Static analysis and lint checks via qlty (PHPStan, PHP-CS-Fixer, CodeSniffer)
composer format   # Format the codebase via qlty
composer smells   # Advisory code smells (duplication, complexity)
```

### Testing

Run the full test suite before submitting:

```bash
composer test            # Run the PHPUnit sniff test suite
composer test:coverage   # With Clover coverage report (requires Xdebug)
```

Single test file or method:

```bash
vendor/bin/phpunit SineMacula/Tests/Sniffs/<Category>/<Name>SniffTest.php
vendor/bin/phpunit --filter testDetectsViolations SineMacula/Tests/Sniffs/<Category>/<Name>SniffTest.php
```

### Standards

- New sniffs and PHPStan rules ship with tests and maintain 100% line coverage
- Full type hints on all method parameters and return types
- PHPDoc on all classes and methods
- Sniffs are token-based; PHPStan rules are AST/reflection-based

## Pull Requests

- Keep changes minimal and scoped to a single concern
- Do not change static-analysis or formatting configuration without prior discussion
- Include tests for new or changed behaviour
- Ensure `composer check` and `composer test` pass

## Security

If you discover a security vulnerability, please report it directly to Sine Macula rather than opening a public issue.
See [SECURITY.md](SECURITY.md) for details.

## License

By contributing, you agree that your contributions will be licensed under the [Apache License 2.0](LICENSE).
