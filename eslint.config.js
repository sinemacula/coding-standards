import base from './js/eslint/index.js';

/**
 * Dogfood config: lint this package's own source with its own rules. The base
 * config's file scoping already covers the JavaScript implementations under
 * js/eslint and exempts the test specs from the metric rules; the ignores below
 * bound the rest, so `eslint .` reaches the rule source and this repository's
 * own workflow YAML, whose comment width the base config now governs too.
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
