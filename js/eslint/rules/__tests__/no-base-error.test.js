/**
 * Tests for the no-base-error rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import rule from '../no-base-error.js';
import { ruleTester } from './tester.js';

// The rule is syntactic and needs no parser services, so the type-free tester
// stands in for the "no type information available" case.
ruleTester.run('no-base-error', rule, {
    valid: [
        // A domain-specific subclass is exactly what the rule asks for.
        'throw new NotFoundError();',
        'throw new ValidationError("bad input");',
        // The specific built-ins are domain enough and are left alone.
        'throw new TypeError("not a number");',
        'throw new RangeError();',
        // Re-throwing a caught value is covered by the only-throw-error
        // concern.
        'function f(err: unknown) { throw err; }',
        'try { work(); } catch (err) { throw err; }',
        // Constructing the base Error for a non-throw use is fine.
        'const e = new Error("boom");',
        'function make() { return new Error("x"); }',
        'const errors = [new Error("a"), new Error("b")];',
        // Value throws are outside this rule's pattern.
        'throw "boom";',
        'throw { message: "x" };',
        // Only a direct construction is inspected, not a conditional or a
        // qualified name.
        'throw cond ? new Error() : new NotFoundError();',
        'throw new foo.Error("x");',
        // A namespaced Error under a non-global object is a domain class, not
        // the base.
        'throw new errors.Error("x");',
        'throw new lib.window.Error("x");',
        // A near-miss name is not the base Error.
        'throw new ErrorReport();',
        // The allow option permits the base construction in every form it can
        // take.
        { code: 'throw new Error("x");', options: [{ allow: ['Error'] }] },
        { code: 'throw new globalThis.Error("x");', options: [{ allow: ['Error'] }] },
        { code: 'throw new Error("x") as CustomError;', options: [{ allow: ['Error'] }] },
        // Test files are exempt: stubs and fakes legitimately throw the base
        // Error.
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
        // The argument-free construction with no parentheses is still a throw
        // of Error.
        {
            code: 'throw new Error;',
            errors: [{ messageId: 'baseError' }],
        },
        {
            code: 'throw new Error(`interpolated ${value}`);',
            errors: [{ messageId: 'baseError' }],
        },
        // Wrapping a caught error in a base Error is still throwing the base.
        {
            code: 'try { work(); } catch { throw new Error("wrapped"); }',
            errors: [{ messageId: 'baseError' }],
        },
        {
            code: 'function f() { throw new Error("x"); }',
            errors: [{ messageId: 'baseError' }],
        },
        // A global object's Error member resolves to the base Error.
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
        // A type annotation on the throw does not change the base construction
        // underneath.
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
        // Stacked annotations and a global callee still resolve to the base
        // Error.
        {
            code: 'throw new globalThis.Error("x") as CustomError as unknown;',
            errors: [{ messageId: 'baseError' }],
        },
        // Only the base Error is ever a candidate, so a non-Error allow entry
        // cannot permit it.
        {
            code: 'throw new Error("x");',
            options: [{ allow: ['LegacyError'] }],
            errors: [{ messageId: 'baseError' }],
        },
    ],
});
