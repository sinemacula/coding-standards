/**
 * RuleTester instances bridged onto vitest's lifecycle.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import path from 'node:path';
import { RuleTester } from '@typescript-eslint/rule-tester';
import { afterAll, describe, it } from 'vitest';

// Bridge @typescript-eslint/rule-tester onto vitest's lifecycle globals.
RuleTester.afterAll = afterAll;
RuleTester.describe = describe;
RuleTester.it = it;
RuleTester.itOnly = it.only;

const fixtures = path.join(import.meta.dirname, 'fixtures');

/**
 * Syntax-only tester. Use for rules that inspect the AST but never read types.
 */
export const ruleTester = new RuleTester();

/**
 * Type-aware tester. Use for rules that call getTypeAtLocation and friends;
 * types resolve against fixtures/tsconfig.json. RuleTester writes each test case
 * into file.ts (or react.tsx for JSX), which the tsconfig includes.
 */
export const typedRuleTester = new RuleTester({
    languageOptions: {
        parserOptions: {
            project: './tsconfig.json',
            tsconfigRootDir: fixtures,
        },
    },
});
