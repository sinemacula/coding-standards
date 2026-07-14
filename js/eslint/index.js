import tseslint from 'typescript-eslint';
import plugin from './plugin.js';

const TS_FILES = ['**/*.ts', '**/*.tsx', '**/*.mts', '**/*.cts'];
const TS_AND_JS_FILES = [...TS_FILES, '**/*.js', '**/*.jsx', '**/*.mjs', '**/*.cjs'];

/**
 * Base flat config: the AST-only custom rules that need no type information.
 *
 * Requires no tsconfig, so it stays cheap. The typescript-eslint parser resolves
 * TypeScript syntax. The interface, readonly-property and enum rules target
 * TypeScript-only constructs; no-mutable-static also applies to plain JavaScript
 * (exported let/var, mutable static fields), so it runs across both. The opt-in
 * type-aware layer lives in ./type-checked.js.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
export default [
    {
        files: TS_FILES,
        plugins: {
            '@sinemacula': plugin,
            '@typescript-eslint': tseslint.plugin,
        },
        languageOptions: {
            parser: tseslint.parser,
        },
        rules: {
            '@sinemacula/no-interface-prefix': 'error',
            '@sinemacula/require-readonly-public-property': 'error',
            '@sinemacula/valid-enum-member-name': 'error',

            '@typescript-eslint/no-explicit-any': 'error',
        },
    },
    {
        files: TS_AND_JS_FILES,
        plugins: {
            '@sinemacula': plugin,
        },
        languageOptions: {
            parser: tseslint.parser,
        },
        rules: {
            '@sinemacula/no-mutable-static': 'error',
            '@sinemacula/max-methods-per-class': 'error',
            '@sinemacula/no-base-error': 'error',
            '@sinemacula/require-copyright': 'error',

            'max-lines-per-function': ['error', { max: 50, skipComments: true, skipBlankLines: true, IIFEs: true }],
            'max-depth': ['error', 4],
        },
    },
    {
        files: ['**/*.{test,spec}.{ts,tsx,mts,cts,js,jsx,mjs,cjs}', '**/__tests__/**', '**/tests/**'],
        rules: {
            'max-lines-per-function': 'off',
            'max-depth': 'off',
        },
    },
];
