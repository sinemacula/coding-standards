import tseslint from 'typescript-eslint';
import base from './index.js';
import plugin from './plugin.js';

/**
 * Opt-in type-aware layer over the base config, for repos with a tsconfig.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
export default [
    ...base,
    {
        files: ['**/*.ts', '**/*.tsx', '**/*.mts', '**/*.cts'],
        plugins: {
            '@sinemacula': plugin,
            '@typescript-eslint': tseslint.plugin,
        },
        languageOptions: {
            parser: tseslint.parser,
            parserOptions: {
                projectService: true,
            },
        },
        rules: {
            '@sinemacula/boolean-method-name': 'error',

            '@typescript-eslint/await-thenable': 'error',
            '@typescript-eslint/consistent-type-imports': 'error',
            // Type-aware replacement for Biome's useLiteralKeys.
            '@typescript-eslint/dot-notation': ['error', { allowIndexSignaturePropertyAccess: true }],
            '@typescript-eslint/explicit-module-boundary-types': 'error',
            '@typescript-eslint/no-floating-promises': 'error',
            '@typescript-eslint/no-misused-promises': 'error',
            '@typescript-eslint/only-throw-error': 'error',
        },
    },
];
