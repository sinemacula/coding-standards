/**
 * Tests for the no-base-error rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import rule from '../no-base-error.js';
import { ruleTester } from './tester.js';

ruleTester.run('no-base-error', rule, {
    valid: [
        'throw new NotFoundError();',
        'throw new ValidationError("bad input");',
        // Specific built-ins are domain enough.
        'throw new TypeError("not a number");',
        'throw new RangeError();',
        // Re-throws are only-throw-error's concern.
        'function f(err: unknown) { throw err; }',
        'try { work(); } catch (err) { throw err; }',
        'const e = new Error("boom");',
        'function make() { return new Error("x"); }',
        'const errors = [new Error("a"), new Error("b")];',
        'throw "boom";',
        'throw { message: "x" };',
        // Only a direct construction is inspected.
        'throw cond ? new Error() : new NotFoundError();',
        'throw new foo.Error("x");',
        // An Error under a non-global object is a domain class.
        'throw new errors.Error("x");',
        'throw new lib.window.Error("x");',
        'throw new ErrorReport();',
        { code: 'throw new Error("x");', options: [{ allow: ['Error'] }] },
        { code: 'throw new globalThis.Error("x");', options: [{ allow: ['Error'] }] },
        { code: 'throw new Error("x") as CustomError;', options: [{ allow: ['Error'] }] },
        // Test stubs legitimately throw the base Error.
        { code: 'throw new Error("not implemented");', filename: 'client.test.ts' },
        { code: 'throw new Error("boom");', filename: '/repo/__tests__/client.ts' },
    ],
    invalid: [
        {
            code: 'throw new Error("boom");',
            errors: [{ messageId: 'baseError' }],
        },
        {
            code: 'throw new Error();',
            errors: [{ messageId: 'baseError' }],
        },
        {
            code: 'throw new Error;',
            errors: [{ messageId: 'baseError' }],
        },
        {
            code: 'throw new Error(`interpolated ${value}`);',
            errors: [{ messageId: 'baseError' }],
        },
        {
            code: 'try { work(); } catch { throw new Error("wrapped"); }',
            errors: [{ messageId: 'baseError' }],
        },
        {
            code: 'function f() { throw new Error("x"); }',
            errors: [{ messageId: 'baseError' }],
        },
        {
            code: 'throw new globalThis.Error("x");',
            errors: [{ messageId: 'baseError' }],
        },
        {
            code: 'throw new window.Error();',
            errors: [{ messageId: 'baseError' }],
        },
        {
            code: 'throw new global.Error("x");',
            errors: [{ messageId: 'baseError' }],
        },
        {
            code: 'throw new self.Error();',
            errors: [{ messageId: 'baseError' }],
        },
        // Type annotations are unwrapped.
        {
            code: 'throw new Error("x") as CustomError;',
            errors: [{ messageId: 'baseError' }],
        },
        {
            code: 'throw new Error("x")!;',
            errors: [{ messageId: 'baseError' }],
        },
        {
            code: 'throw new Error("x") satisfies Error;',
            errors: [{ messageId: 'baseError' }],
        },
        {
            code: 'throw new globalThis.Error("x") as CustomError as unknown;',
            errors: [{ messageId: 'baseError' }],
        },
        // Only `Error` in allow can permit the base Error.
        {
            code: 'throw new Error("x");',
            options: [{ allow: ['LegacyError'] }],
            errors: [{ messageId: 'baseError' }],
        },
    ],
});
