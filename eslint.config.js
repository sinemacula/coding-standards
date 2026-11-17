import base from './js/eslint/index.js';

/**
 * Lints this package's own rule source and workflow YAML with its own config.
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
