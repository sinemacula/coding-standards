# Shared TFLint policy for Sine Macula Terraform repositories.
#
# Only the ruleset bundled with TFLint is enabled. Qlty installs plugins with
# `tflint --init` inside a sandbox that cannot fetch a pinned ruleset, so a
# provider ruleset declared here would fail every run.
plugin "terraform" {
  enabled = true
  preset  = "all"
}
