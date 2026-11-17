# Terraform standards

The shared Terraform policy is `.tflint.hcl`, which enables TFLint's bundled Terraform ruleset at the `all` preset.

Both tools come from Qlty's default source. The Sine Macula source enables and pins them for every consumer: Terraform
with only its format driver, and TFLint with this config. A repository with no Terraform files runs neither.

The `all` preset adds to the recommended rules: snake_case names, a type and description on every variable, a
description on every output, and the standard module structure, with variables in `variables.tf` and outputs in
`outputs.tf`. No rule is switched off.

TFLint reports a module that lacks `main.tf`, `variables.tf` or `outputs.tf` on the missing file, and Qlty drops
findings on files that do not exist. Through Qlty, the rule therefore enforces where variables and outputs live, not
that the three files exist.

Qlty stages the config at the repository root but runs TFLint from each module's directory, where TFLint would not
find it, so the source passes it with `--config`.

Backends, variable values, provider credentials and module-specific rules stay in the consuming repository.

## Not included

- Provider rulesets such as AWS, Azure and Google. Qlty installs TFLint plugins with `tflint --init` in a sandbox
  that cannot fetch a pinned ruleset, so one declared here would fail every run.
- `terraform validate`. It needs `terraform init`, which Qlty does not run. The reusable `terraform-tests.yml`
  workflow in `sinemacula/.github` validates instead.
