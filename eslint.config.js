import base from './js/eslint/index.js';

/**
 * Dogfood config: lint this package's own rule source with its own rules. The
 * base config's file scoping already covers the JavaScript implementations under
 * js/eslint and exempts the test specs from the metric rules; run it with
 * `eslint js/eslint` to bound it to the rule source.
 */
export default [
    {
        ignores: [
            '**/__tests__/fixtures/**',
            'eslint.config.js',
            'vitest.config.js',
            'vendor/**',
            '.qlty/**',
            '.sinemacula/**',
            '.idea/**',
        ],
    },
    ...base,
];
