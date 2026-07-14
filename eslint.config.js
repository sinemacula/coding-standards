import base from './js/eslint/index.js';

/**
 * Dogfood config: run this package's own ESLint rules over its own rule source.
 * The base config targets TypeScript, so its file scope is remapped to the
 * JavaScript rule implementations under js/eslint. The type-aware layer is
 * exercised by the per-rule RuleTester specs, not by scanning this plain-JS
 * source.
 */
export default [
    { ignores: ['**/__tests__/fixtures/**', 'vendor/**', '.qlty/**', '.sinemacula/**', '.idea/**'] },
    ...base.map(config => ({ ...config, files: ['js/eslint/**/*.js'] })),
];
