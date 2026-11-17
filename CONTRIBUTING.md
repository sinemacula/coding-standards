# Contributing

Contributions are welcome via GitHub pull requests.

## Requirements

- PHP 8.3+
- Composer 2
- Node.js, for the JavaScript, Markdown, YAML and shell configs distributed via npm
- Qlty CLI, for the policy integration tests; the Swift one also needs macOS

## Getting Started

```bash
git clone git@github.com:sinemacula/coding-standards.git
cd coding-standards
composer install
npm ci
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

This project uses [Conventional Commits](https://www.conventionalcommits.org/):

```text
feat(phpcs): add sniff for redundant else branches
feat(phpstan): enforce the #[\Override] attribute
fix(cs-fixer): correct promoted-property formatting
chore: update qlty configuration
```

### Code Quality

All code must pass these before submission:

```bash
composer check    # Static analysis and lint via qlty
composer format   # Format via qlty
composer smells   # Code smells (duplication, complexity) via qlty
composer analyse  # PHPStan over the sniffs and rules
npm run lint:js   # ESLint over the ESLint rules
```

### Testing

Run the full test suite before submitting:

```bash
composer test                 # PHPUnit suite for the sniffs and PHPStan rules
composer test:coverage        # With Clover coverage report (requires Xdebug)
composer test:mutation        # Infection mutation gate on the diff against origin/master
npm run test:js               # Vitest suite for the ESLint rules
bash scripts/test-<tool>-policy.sh  # Qlty consumer integration test for knip, shared, swift, terraform, trivy or zizmor
```

Single test file or method:

```bash
vendor/bin/phpunit SineMacula/Tests/<Category>/<Name>SniffTest.php
vendor/bin/phpunit --filter <testMethod> SineMacula/Tests/<Category>/<Name>SniffTest.php
```

### Standards

- New sniffs and PHPStan rules ship with tests and 100% line coverage; `composer test:mutation` is the enforced floor
- Changes to an exported Qlty policy must pass its consumer integration test (`scripts/test-*-policy.sh`)
- Full type hints on all method parameters and return types
- PHPDoc on all classes and methods
- Sniffs are token-based; PHPStan rules are AST/reflection-based

## Pull Requests

- Keep changes scoped to a single concern
- Do not change static-analysis or formatting configuration without prior discussion
- Include tests for new or changed behaviour
- Ensure `composer check` and `composer test` pass

## Security

Report vulnerabilities directly to Sine Macula rather than opening a public issue. See [SECURITY.md](SECURITY.md).

## License

By contributing, you agree that your contributions will be licensed under the [Apache License 2.0](LICENSE).
