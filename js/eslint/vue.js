import checkFile from 'eslint-plugin-check-file';
import pluginVue from 'eslint-plugin-vue';
import tseslint from 'typescript-eslint';

/**
 * Opt-in Vue layer for single-file components, including template layout, which
 * Biome cannot format.
 *
 * It carries no base rules, so spread it after the base or type-aware layer.
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
                '**/*.vue': 'PASCAL_CASE',
            }, {
                ignoreMiddleExtensions: true,
            }],
        },
    },
];
