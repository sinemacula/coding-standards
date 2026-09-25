#!/usr/bin/env bash

set -euo pipefail

if ! command -v qlty >/dev/null 2>&1; then
    echo "The Qlty CLI is required: https://docs.qlty.sh/cli/quickstart" >&2
    exit 1
fi

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# TMPDIR is unset in many shells. Left bare, the template collapses to a path at
# the filesystem root and mktemp fails with an error that names neither cause.
trivy_consumer_dir="$(mktemp -d "${TMPDIR:-/tmp}/coding-standards-trivy.XXXXXX")"

cleanup() {
    rm -rf -- "${trivy_consumer_dir}"
}

trap cleanup EXIT

mkdir -p "${trivy_consumer_dir}/.qlty"
cp "${repository_root}/tests/trivy-consumer/Dockerfile" "${trivy_consumer_dir}/Dockerfile"
sed "s|__SOURCE_DIRECTORY__|${repository_root}|g" \
    "${repository_root}/tests/trivy-consumer/qlty.toml.template" \
    > "${trivy_consumer_dir}/.qlty/qlty.toml"

# The same misconfigured manifest is planted where a repo would author it and in
# every tree the shared config skips, so only the authored copy may be reported.
manifest="${repository_root}/tests/trivy-consumer/Fixtures/pod.yaml.fixture"

for directory in deploy .qlty/out vendor/acme app/vendor/acme node_modules/acme app/node_modules/acme; do
    mkdir -p "${trivy_consumer_dir}/${directory}"
    cp "${manifest}" "${trivy_consumer_dir}/${directory}/pod.yaml"
done

git -C "${trivy_consumer_dir}" init --quiet --initial-branch=master

cd "${trivy_consumer_dir}"

set +e
scan_output="$(qlty check --all --no-cache --filter=trivy 2>&1)"
set -e

fail() {
    echo "$1"
    echo "${scan_output}"
    exit 1
}

# Authored files must still be reported, or a green run would prove nothing.
for reported in Dockerfile deploy/pod.yaml; do
    if [[ ${scan_output} != *"${reported}"* ]]; then
        fail "Expected Trivy to report the misconfiguration in ${reported}."
    fi
done

# qlty drops vendor and node_modules paths from its own report whatever Trivy
# scanned, so what Trivy walked is read from its raw output in the invocation
# record instead.
trivy_record="$(grep -l 'trivy config' .qlty/out/invoke-*.yaml)"

if ! grep -qF 'Loaded\tfile_path=\"trivy.yaml\"' "${trivy_record}"; then
    fail "Expected qlty to stage the exported trivy.yaml where Trivy loads it."
fi

for skipped in .qlty/out/pod.yaml vendor/acme/pod.yaml app/vendor/acme/pod.yaml \
    node_modules/acme/pod.yaml app/node_modules/acme/pod.yaml; do
    if grep -qF "\"uri\": \"${skipped}\"" "${trivy_record}"; then
        fail "Expected the shared Trivy config to skip ${skipped}."
    fi
done
