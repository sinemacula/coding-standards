# Swift standards

The shared Swift 6 policy consists of:

- `.swiftlint.yml` for correctness, concurrency, safety, performance, metrics, and style findings.
- `.swiftformat` for deterministic source formatting.

Both tools are provided by Qlty's default source. Consumers enable the `swiftlint` and `swiftformat` plugins and receive
these configurations from the Sine Macula source declared in `.qlty/qlty.toml`.

SwiftLint and SwiftFormat run through Qlty CLI only on macOS. Qlty Cloud still provides Swift maintainability analysis,
but its Linux workers cannot execute these two native plugins. A Swift repository therefore needs a macOS quality job
that runs both `qlty fmt --all` and `qlty check --all`.

The policy is not purely stock SwiftLint. Where the PHP and TypeScript standards in this repository express a house
opinion that SwiftLint can express too, it is carried over: a required copyright header (`file_header`), documentation
on the public surface (`missing_docs`), and comment prose held to the line limit. Opinions with no SwiftLint
equivalent - a maximum method count per type, protocol and boolean-method naming, and a required-readonly property
rule - are not enforced for Swift, and closing those would mean writing custom regex rules.

Application-specific paths, generated-source conventions, and architectural restrictions stay in the consuming
repository. The shared policy must remain usable by macOS apps, iOS apps, command-line tools, and Swift packages.

The standard deliberately avoids formatter rules that can change ownership, control flow, or an API declaration. A
formatter should make an equivalent program consistent; correctness and design changes belong in reviewed source
edits.

## Deliberately not included

- StringsLint is deferred because its current Qlty integration does not support modern `.xcstrings` String Catalogs.
  Reconsider it for a consumer that deliberately uses legacy `.strings` or `.stringsdict` resources.
- Semgrep rules belong here only after an organization-wide Swift security or architecture rule is defined. Product-
  specific boundaries should stay in the product repository.
- Compiler-enforced policy such as strict concurrency, warnings-as-errors, deployment targets, and platform
  availability remains in each Xcode project or shared project template; Qlty is not a substitute for `xcodebuild`.
