# Only the bundled ruleset: qlty's sandbox cannot fetch a pinned provider
# ruleset, so declaring one would fail every run.
plugin "terraform" {
  enabled = true
  preset  = "all"
}
