# Terraform standards

The shared Terraform policy consists of:

- `.tflint.hcl` for TFLint's bundled Terraform ruleset at the `all` preset.

Both tools are provided by Qlty's default source. The Sine Macula source enables and pins them for every consumer:
Terraform 1.16.5 with only its format driver, and TFLint 0.64.0 with this configuration. A repository with no
Terraform files runs neither.

The `all` preset adds to the recommended rules: snake_case names, a type and a description on every variable, a
description on every output, and the standard module structure, with variables in `variables.tf` and outputs in
`outputs.tf`. Documentation on the public surface is the same house opinion the PHP and Swift standards hold. No rule
is switched off.

Qlty stages the configuration at the repository root but runs TFLint from each module's directory, where TFLint would
not find it. The source therefore passes it with `--config`, so a nested module gets the same policy as the root.

Backends, variable values, provider credentials and module-specific rules stay in the consuming repository.

## Deliberately not included

- Provider rulesets such as AWS, Azure and Google. Qlty installs TFLint plugins with `tflint --init` in a sandbox
  that cannot fetch a pinned ruleset, so one declared here would fail every run.
- `terraform validate`. It needs `terraform init`, which Qlty does not run. The reusable `terraform-tests.yml`
  workflow in `sinemacula/.github` validates instead.
