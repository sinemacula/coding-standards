import path from 'node:path';
import { pathToFileURL } from 'node:url';

/**
 * Dogfood config: run this package's own ESLint rules over its own rule source,
 * the parallel of running the phpcs ruleset over SineMacula/Sniffs. The base
 * config targets TypeScript, so its file scope is remapped to the JavaScript
 * rule implementations under js/eslint. The type-aware layer is exercised by
 * the per-rule RuleTester specs, not by scanning this plain-JS source.
 *
 * Qlty copies this file into its eslint sandbox before running it, so the base
 * config is loaded by an absolute path resolved from the working directory
 * rather than a relative import, which would resolve against the sandbox.
 */
const { default: base } = await import(pathToFileURL(path.resolve('js/eslint/index.js')).href);

export default [
    { ignores: ['**/__tests__/fixtures/**', 'vendor/**', '.qlty/**', '.sinemacula/**', '.idea/**'] },
    ...base.map(config => ({ ...config, files: ['js/eslint/**/*.js'] })),
];
