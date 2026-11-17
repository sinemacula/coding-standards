#!/usr/bin/env bash

set -euo pipefail

if ! command -v qlty >/dev/null 2>&1; then
    echo "The Qlty CLI is required: https://docs.qlty.sh/cli/quickstart" >&2
    exit 1
fi

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# TMPDIR is often unset; without a fallback mktemp targets the filesystem root.
zizmor_consumer_dir="$(mktemp -d "${TMPDIR:-/tmp}/coding-standards-zizmor.XXXXXX")"

cleanup() {
    rm -rf -- "${zizmor_consumer_dir}"
}

trap cleanup EXIT

mkdir -p "${zizmor_consumer_dir}/.qlty" "${zizmor_consumer_dir}/.github/workflows"
sed "s|__SOURCE_DIRECTORY__|${repository_root}|g" \
    "${repository_root}/tests/zizmor-consumer/qlty.toml.template" \
    > "${zizmor_consumer_dir}/.qlty/qlty.toml"
cp "${repository_root}/tests/zizmor-consumer/Fixtures/audit.yml.fixture" \
    "${zizmor_consumer_dir}/.github/workflows/audit.yml"

git -C "${zizmor_consumer_dir}" init --quiet --initial-branch=master

cd "${zizmor_consumer_dir}"

set +e
scan_output="$(qlty check --all --no-cache --filter=zizmor 2>&1)"
set -e

fail() {
    echo "$1"
    echo "${scan_output}"
    exit 1
}

# The fixture's lines 13 to 17 are the checkout, the two organizations' actions,
# the third-party action and the injected run step.
expect_reported() {
    if ! grep -Eq "^ +$1:[0-9]+ .*zizmor/$2" <<< "${scan_output}"; then
        fail "Expected zizmor to report $2 on line $1."
    fi
}

expect_silent() {
    if grep -Eq "^ +$1:[0-9]+ .*zizmor/" <<< "${scan_output}"; then
        fail "Expected zizmor to report nothing on line $1."
    fi
}

# A third-party tag and an injected expression must still be reported, or a
# green run would prove nothing.
expect_reported 16 unpinned-uses
expect_reported 17 template-injection

# The shared policy lets GitHub's and both organizations' actions keep a tag,
# and turns off the checkout credential audit.
expect_silent 13
expect_silent 14
expect_silent 15
