#!/usr/bin/env bash

set -euo pipefail

if ! command -v qlty >/dev/null 2>&1; then
    echo "The Qlty CLI is required: https://docs.qlty.sh/cli/quickstart" >&2
    exit 1
fi

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# TMPDIR is unset in many shells. Left bare, the template collapses to a path at
# the filesystem root and mktemp fails with an error that names neither cause.
smells_consumer_dir="$(mktemp -d "${TMPDIR:-/tmp}/coding-standards-smells.XXXXXX")"

cleanup() {
    rm -rf -- "${smells_consumer_dir}"
}

trap cleanup EXIT

# The consumer carries no [smells] of its own, so everything below comes from
# the shared source or it comes from qlty's defaults.
mkdir -p "${smells_consumer_dir}/.qlty" "${smells_consumer_dir}/src"
sed "s|__SOURCE_DIRECTORY__|${repository_root}|g" \
    "${repository_root}/tests/smells-consumer/qlty.toml.template" \
    > "${smells_consumer_dir}/.qlty/qlty.toml"
cp "${repository_root}/tests/smells-consumer/Signals.php.fixture" "${smells_consumer_dir}/src/Signals.php"

git -C "${smells_consumer_dir}" init --quiet --initial-branch=master

cd "${smells_consumer_dir}"

smells_output="$(qlty smells --all 2>&1)"
config_output="$(qlty config show 2>&1)"

fail() {
    echo "$1"
    echo "${smells_output}"
    exit 1
}

# qlty's defaults flag both functions: eight parameters and seven returns each
# meet its threshold of six.
if [[ ${smells_output} == *"many parameters"* ]]; then
    fail "Expected the shared policy to switch off the parameter-count smell."
fi

if [[ ${smells_output} == *"many returns"* ]]; then
    fail "Expected the shared policy to raise the return-statement threshold."
fi

if ! awk '/^smells:/ { in_smells = 1; next } in_smells && /^[^ ]/ { exit } in_smells && /^  mode: comment$/ { found = 1 } END { exit !found }' \
    <<< "${config_output}"; then
    fail "Expected the shared policy to report smells as comments."
fi
