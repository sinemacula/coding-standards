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
 */
export default [
    {
        files: TS_FILES,
        plugins: {
            '@sinemacula': plugin,
        },
        languageOptions: {
            parser: tseslint.parser,
        },
        rules: {
            '@sinemacula/no-interface-prefix': 'error',
            '@sinemacula/require-readonly-public-property': 'error',
            '@sinemacula/valid-enum-member-name': 'error',
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
        },
    },
];
