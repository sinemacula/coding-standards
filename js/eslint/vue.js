import checkFile from 'eslint-plugin-check-file';
import pluginVue from 'eslint-plugin-vue';
import tseslint from 'typescript-eslint';

/**
 * Opt-in Vue layer: the single-file-component rules. Registers the SFC parser
 * so `.vue` files are linted at all, resolves `<script lang="ts">` blocks
 * through the TypeScript parser, and holds component filenames to the same
 * kebab-case convention the shared formatter enforces on plain sources.
 *
 * This layer also carries the template layout rules, which the shared formatter
 * cannot: it does not understand single-file components, so `.vue` markup would
 * otherwise go unformatted entirely.
 *
 * Additive, and deliberately unlike the type-aware layer: it carries no base
 * rules of its own, so spread it after whichever layer a repo already uses
 * rather than in place of one. Vue is orthogonal to type-awareness, and a repo
 * enabling both would otherwise apply the base rules twice.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
export default [
    ...pluginVue.configs['flat/recommended-error'],
    {
        files: ['**/*.vue'],
        plugins: {
            'check-file': checkFile,
        },
        languageOptions: {
            parserOptions: {
                parser: tseslint.parser,
            },
        },
        rules: {
            // The template rules indent by two; the shared formatter is four.
            'vue/html-indent': ['error', 4],

            'check-file/filename-naming-convention': ['error', {
                '**/*.vue': 'KEBAB_CASE',
            }, {
                ignoreMiddleExtensions: true,
            }],
        },
    },
];
