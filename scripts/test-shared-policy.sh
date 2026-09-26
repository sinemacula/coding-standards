#!/usr/bin/env bash

set -euo pipefail

if ! command -v qlty >/dev/null 2>&1; then
    echo "The Qlty CLI is required: https://docs.qlty.sh/cli/quickstart" >&2
    exit 1
fi

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# TMPDIR is unset in many shells. Left bare, the template collapses to a path at
# the filesystem root and mktemp fails with an error that names neither cause.
consumer_dir="$(mktemp -d "${TMPDIR:-/tmp}/coding-standards-shared.XXXXXX")"

cleanup() {
    rm -rf -- "${consumer_dir}"
}

trap cleanup EXIT

# The consumer declares nothing but its sources, so everything below comes from
# the shared source or it comes from qlty's defaults.
mkdir -p "${consumer_dir}/.qlty" "${consumer_dir}/src"
sed "s|__SOURCE_DIRECTORY__|${repository_root}|g" \
    "${repository_root}/tests/shared-consumer/qlty.toml.template" \
    > "${consumer_dir}/.qlty/qlty.toml"
cp "${repository_root}/tests/shared-consumer/Signals.php.fixture" "${consumer_dir}/src/Signals.php"

git -C "${consumer_dir}" init --quiet --initial-branch=master

cd "${consumer_dir}"

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

# Prints the given plugin's entry from the merged config, one key per line.
plugin_entry() {
    awk -v name="$1" '
        /^plugin:/ { in_plugins = 1; next }
        in_plugins && /^[^ -]/ { exit }
        in_plugins && /^- name: / { in_entry = ($3 == name) }
        in_entry { print }
    ' <<< "${config_output}"
}

expect_entry() {
    local plugin="$1" pattern="$2"

    if ! grep -qE "${pattern}" <<< "$(plugin_entry "${plugin}")"; then
        fail "Expected every consumer to run ${plugin} with ${pattern}."
    fi
}

for plugin in trufflehog yamllint actionlint osv-scanner editorconfig-checker php-cs-fixer; do
    expect_entry "${plugin}" "^- name: ${plugin}$"
done

expect_entry markdownlint '^  mode: comment$'
expect_entry php-codesniffer '^  version: 4\.0\.4$'
expect_entry php-codesniffer '^  mode: comment$'
expect_entry php-cs-fixer '^  version: 3\.95\.25$'
expect_entry radarlint-php '^  mode: comment$'
expect_entry trivy '^  - fs-vuln$'

if grep -qE '^  - ALL$' <<< "$(plugin_entry trivy)"; then
    fail "Expected every consumer to run only the chosen Trivy scanners."
fi
