#!/usr/bin/env bash

set -euo pipefail

if ! command -v qlty >/dev/null 2>&1; then
    echo "The Qlty CLI is required: https://docs.qlty.sh/cli/quickstart" >&2
    exit 1
fi

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
fixtures="${repository_root}/tests/vue-consumer/Fixtures"

# TMPDIR is often unset; without a fallback mktemp targets the filesystem root.
vue_consumer_dir="$(mktemp -d "${TMPDIR:-/tmp}/coding-standards-vue.XXXXXX")"

cleanup() {
    rm -rf -- "${vue_consumer_dir}"
}

trap cleanup EXIT

# The consumer's config is the README's Vue example verbatim, so the docs cannot
# drift from what this proves.
documented_config="$(awk '
    /^With the Vue layer, add its packages/ { found = 1; next }
    found && /^```toml$/ { inside = 1; next }
    inside && /^```$/ { exit }
    inside { print }
' "${repository_root}/README.md")"

if [[ -z ${documented_config} ]]; then
    echo "Expected the README to document the Qlty config for the Vue layer."
    exit 1
fi

mkdir -p "${vue_consumer_dir}/.qlty/configs" "${vue_consumer_dir}/src/components"
sed "s|__SOURCE_DIRECTORY__|${repository_root}|g" \
    "${repository_root}/tests/vue-consumer/qlty.toml.template" \
    > "${vue_consumer_dir}/.qlty/qlty.toml"
printf '\n%s\n' "${documented_config}" >> "${vue_consumer_dir}/.qlty/qlty.toml"

# qlty keys its tool sandbox on the package file, so a content-addressed tarball
# stops a local rerun from linting with a stale copy of this package.
packed="$(cd "${repository_root}" && npm pack --silent --pack-destination "${vue_consumer_dir}")"
tarball="coding-standards-$(shasum -a 256 "${vue_consumer_dir}/${packed}" | cut -c1-12).tgz"
mv "${vue_consumer_dir}/${packed}" "${vue_consumer_dir}/${tarball}"

sed "s|__PACKAGE_TARBALL__|${tarball}|" "${fixtures}/package.json.fixture" > "${vue_consumer_dir}/package.json"
cp "${fixtures}/eslint.config.mjs.fixture" "${vue_consumer_dir}/.qlty/configs/eslint.config.mjs"
cp "${fixtures}/ListItems.vue.fixture" "${vue_consumer_dir}/src/components/ListItems.vue"

git -C "${vue_consumer_dir}" init --quiet --initial-branch=master

cd "${vue_consumer_dir}"

set +e
scan_output="$(qlty check --all --no-cache --filter=eslint 2>&1)"
set -e

fail() {
    echo "$1"
    echo "${scan_output}"
    exit 1
}

if [[ ${scan_output} != *"src/components/ListItems.vue"* ]]; then
    fail "Expected ESLint to lint the single-file component."
fi

for rule in vue/no-use-v-if-with-v-for vue/require-v-for-key; do
    if [[ ${scan_output} != *"eslint:${rule}"* ]]; then
        fail "Expected the Vue layer to report ${rule}."
    fi
done
