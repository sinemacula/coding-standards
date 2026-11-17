/**
 * RuleTester instances bridged onto vitest's lifecycle.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import path from 'node:path';
import { RuleTester } from '@typescript-eslint/rule-tester';
import { afterAll, describe, it } from 'vitest';
import * as yamlParser from 'yaml-eslint-parser';

RuleTester.afterAll = afterAll;
RuleTester.describe = describe;
RuleTester.it = it;
RuleTester.itOnly = it.only;

const fixtures = path.join(import.meta.dirname, 'fixtures');

/** Syntax-only tester for rules that never read types. */
export const ruleTester = new RuleTester();

/**
 * Type-aware tester; each case is written to fixtures/file.ts (or react.tsx).
 */
export const typedRuleTester = new RuleTester({
    languageOptions: {
        parserOptions: {
            project: './tsconfig.json',
            tsconfigRootDir: fixtures,
        },
    },
});

/** YAML tester; the parser surfaces a `#` comment as a `Block` comment. */
export const yamlRuleTester = new RuleTester({
    languageOptions: {
        parser: yamlParser,
    },
});
