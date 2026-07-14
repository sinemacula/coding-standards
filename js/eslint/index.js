import tseslint from 'typescript-eslint';
import plugin from './plugin.js';

/**
 * Base flat config: the syntax-only custom structural rules.
 *
 * Requires no tsconfig, so it stays cheap. The typescript-eslint parser is set
 * for TypeScript files so the AST-only custom rules resolve without type
 * information. This layer carries only the structural rules that need no type
 * data; the opt-in type-aware layer lives in ./type-checked.js.
 */
export default [
    {
        files: ['**/*.ts', '**/*.tsx', '**/*.mts', '**/*.cts'],
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
];
