#!/usr/bin/env bash

set -euo pipefail

if ! command -v qlty >/dev/null 2>&1; then
    echo "The Qlty CLI is required: https://docs.qlty.sh/cli/quickstart" >&2
    exit 1
fi

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# TMPDIR is unset in many shells. Left bare, the template collapses to a path at
# the filesystem root and mktemp fails with an error that names neither cause.
terraform_consumer_dir="$(mktemp -d "${TMPDIR:-/tmp}/coding-standards-terraform.XXXXXX")"

cleanup() {
    rm -rf -- "${terraform_consumer_dir}"
}

trap cleanup EXIT

# The consumer declares no plugins, so Terraform and TFLint run only because the
# shared source enables them.
mkdir -p "${terraform_consumer_dir}/.qlty"
cp -R "${repository_root}/tests/terraform-consumer/modules" "${terraform_consumer_dir}/modules"
sed "s|__SOURCE_DIRECTORY__|${repository_root}|g" \
    "${repository_root}/tests/terraform-consumer/qlty.toml.template" \
    > "${terraform_consumer_dir}/.qlty/qlty.toml"

git -C "${terraform_consumer_dir}" init --quiet --initial-branch=master

cd "${terraform_consumer_dir}"
qlty fmt --all
diff -ru "${repository_root}/tests/terraform-consumer/modules" modules
qlty check --all --no-cache --no-formatters

# Undocumented variables are reported only at the "all" preset, and TFLint runs
# from the module's own directory, away from where qlty stages the config. This
# finding therefore proves the exported policy reached a nested module.
cp "${repository_root}/tests/terraform-consumer/Fixtures/undocumented.tf.fixture" \
    modules/greeting/undocumented.tf

set +e
lint_output="$(qlty check modules/greeting/undocumented.tf --no-cache --no-formatters --filter=tflint 2>&1)"
lint_status=$?
set -e

if [[ ${lint_status} -eq 0 ]]; then
    echo "Expected the shared TFLint policy to reject an undocumented variable."
    exit 1
fi

if [[ ${lint_output} != *"terraform_documented_variables"* ]]; then
    echo "TFLint failed without reporting the expected terraform_documented_variables rule."
    echo "${lint_output}"
    exit 1
fi

# terraform fmt aligns the equals signs of consecutive attributes, so the
# unaligned line must not survive formatting.
cp "${repository_root}/tests/terraform-consumer/Fixtures/unaligned.tf.fixture" \
    modules/greeting/unaligned.tf
qlty fmt modules/greeting/unaligned.tf

if grep -qF '  name = "greeting"' modules/greeting/unaligned.tf; then
    echo "Expected the shared Terraform formatter to align the attributes."
    cat modules/greeting/unaligned.tf
    exit 1
fi
