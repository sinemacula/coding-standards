#!/usr/bin/env bash

set -euo pipefail

if ! command -v qlty >/dev/null 2>&1; then
    echo "The Qlty CLI is required: https://docs.qlty.sh/cli/quickstart" >&2
    exit 1
fi

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# TMPDIR is unset in many shells. Left bare, the template collapses to a path at
# the filesystem root and mktemp fails with an error that names neither cause.
swift_consumer_dir="$(mktemp -d "${TMPDIR:-/tmp}/coding-standards-swift.XXXXXX")"

cleanup() {
    rm -rf -- "${swift_consumer_dir}"
}

trap cleanup EXIT

mkdir -p "${swift_consumer_dir}/.qlty"
cp -R "${repository_root}/tests/swift-consumer/Sources" "${swift_consumer_dir}/Sources"
sed "s|__SOURCE_DIRECTORY__|${repository_root}|g" \
    "${repository_root}/tests/swift-consumer/qlty.toml.template" \
    > "${swift_consumer_dir}/.qlty/qlty.toml"

git -C "${swift_consumer_dir}" init --quiet --initial-branch=master

cd "${swift_consumer_dir}"
qlty fmt --all
diff -ru "${repository_root}/tests/swift-consumer/Sources" Sources
qlty check --all --no-formatters

# force_unwrapping is opt-in, so this expected failure proves the exported
# SwiftLint policy was loaded instead of SwiftLint's default configuration.
cp "${repository_root}/tests/swift-consumer/Fixtures/ForceUnwrap.swift.fixture" \
    Sources/ForceUnwrap.swift

set +e
lint_output="$(qlty check Sources/ForceUnwrap.swift --no-formatters 2>&1)"
lint_status=$?
set -e

if [[ ${lint_status} -eq 0 ]]; then
    echo "Expected the shared SwiftLint policy to reject force-unwrapping."
    exit 1
fi

if [[ ${lint_output} != *"force_unwrapping"* ]]; then
    echo "SwiftLint failed without reporting the expected force_unwrapping rule."
    echo "${lint_output}"
    exit 1
fi
