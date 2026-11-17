# Swift standards

The shared Swift 6 policy:

- `.swiftlint.yml` for correctness, concurrency, safety, performance, metrics and style findings.
- `.swiftformat` for deterministic source formatting.

Both tools come from Qlty's default source. Consumers enable the `swiftlint` and `swiftformat` plugins and receive
these configs from the Sine Macula source declared in `.qlty/qlty.toml`.

SwiftLint and SwiftFormat run through the Qlty CLI on macOS only. Qlty Cloud's Linux workers cannot execute them, so a
Swift repository needs a macOS quality job that runs `qlty fmt --all` and `qlty check --all`.

Where SwiftLint can express a rule the PHP and TypeScript standards enforce, the policy carries it over: a required
copyright header (`file_header`), documentation on the public surface (`missing_docs`) and comment prose held to the
line limit. Rules with no SwiftLint equivalent (a maximum method count per type, protocol and boolean-method naming,
required-readonly properties) are not enforced for Swift; they would need custom regex rules.

Application-specific paths, generated-source conventions and architectural restrictions stay in the consuming
repository. The policy must stay usable by macOS apps, iOS apps, command-line tools and Swift packages.

Formatter rules that can change ownership, control flow or an API declaration are disabled; those changes belong in
reviewed source edits.

## Not included

- StringsLint: its Qlty integration does not support `.xcstrings` String Catalogs. Reconsider it for a consumer that
  uses legacy `.strings` or `.stringsdict` resources.
- Semgrep rules: only once an organization-wide Swift security or architecture rule is defined. Product-specific
  boundaries stay in the product repository.
- Compiler-enforced policy such as strict concurrency, warnings-as-errors, deployment targets and platform
  availability: this stays in each Xcode project or project template. Qlty is not a substitute for `xcodebuild`.
