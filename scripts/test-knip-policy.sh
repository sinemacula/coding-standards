#!/usr/bin/env bash

set -euo pipefail

if ! command -v qlty >/dev/null 2>&1; then
    echo "The Qlty CLI is required: https://docs.qlty.sh/cli/quickstart" >&2
    exit 1
fi

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
fixtures="${repository_root}/tests/knip-consumer/Fixtures"

# TMPDIR is often unset; without a fallback mktemp targets the filesystem root.
knip_consumer_dir="$(mktemp -d "${TMPDIR:-/tmp}/coding-standards-knip.XXXXXX")"

cleanup() {
    rm -rf -- "${knip_consumer_dir}"
}

trap cleanup EXIT

mkdir -p "${knip_consumer_dir}/.qlty" "${knip_consumer_dir}/src"
sed "s|__SOURCE_DIRECTORY__|${repository_root}|g" \
    "${repository_root}/tests/knip-consumer/qlty.toml.template" \
    > "${knip_consumer_dir}/.qlty/qlty.toml"
cp "${fixtures}/package.json.fixture" "${knip_consumer_dir}/package.json"

for source_file in index lib orphan; do
    cp "${fixtures}/${source_file}.ts.fixture" "${knip_consumer_dir}/src/${source_file}.ts"
done

git -C "${knip_consumer_dir}" init --quiet --initial-branch=master

cd "${knip_consumer_dir}"

scan_output=""

scan() {
    set +e
    scan_output="$(qlty check --all --no-cache --filter=knip 2>&1)"
    set -e
}

fail() {
    echo "$1"
    echo "${scan_output}"
    exit 1
}

expect_reported() {
    if ! grep -Eq "$1" <<< "${scan_output}"; then
        fail "Expected knip to report $2 $3."
    fi
}

# Without a project config, knip's own defaults must find the entry point and
# report both the unused export and the file nothing imports.
scan
expect_reported 'unused  knip:unused-export' 'the unused export' 'with no project config'
expect_reported 'knip:unused-file' 'the unimported file' 'with no project config'

# A root knip.json must be the only config knip loads: the run has to succeed,
# and the file it tells knip to ignore must drop out of the report.
cp "${fixtures}/knip.json.fixture" knip.json
scan

if grep -q 'Error loading' <<< "${scan_output}"; then
    fail "Expected knip to load the project's own knip.json."
fi

expect_reported 'unused  knip:unused-export' 'the unused export' 'with a project config'

if grep -q 'knip:unused-file' <<< "${scan_output}"; then
    fail "Expected knip to apply the project's own knip.json."
fi
