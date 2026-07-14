/**
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import tseslint from 'typescript-eslint';
import base from './index.js';
import plugin from './plugin.js';

/**
 * Opt-in type-aware layer: the cross-file and type-driven rules. Spreads the
 * base config, then turns on type information via the project service so the
 * type-aware custom rules and the curated typescript-eslint rules can resolve.
 * Layered on only where a consumer tsconfig exists, keeping the base config the
 * cheap fast path.
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
            '@typescript-eslint/explicit-module-boundary-types': 'error',
            '@typescript-eslint/no-floating-promises': 'error',
            '@typescript-eslint/no-misused-promises': 'error',
            '@typescript-eslint/only-throw-error': 'error',
            // '@typescript-eslint/strict-boolean-expressions': 'error',
        },
    },
];
