/**
 * Tests for the max-methods-per-class rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import rule from '../max-methods-per-class.js';
import { ruleTester } from './tester.js';

/** A class body holding `n` distinct empty methods. */
const methods = n => Array.from({ length: n }, (_, i) => `m${i}() {}`).join(' ');

ruleTester.run('max-methods-per-class', rule, {
    valid: [
        // A count equal to the maximum is allowed; only exceeding it is
        // flagged.
        { code: 'class C { a() {} b() {} }', options: [{ max: 2 }] },
        { code: 'class C { a() {} }', options: [{ max: 2 }] },
        'class C {}',
        // The default maximum is twenty.
        `class C { ${methods(20)} }`,
        // The constructor counts, but a class within the limit still passes.
        { code: 'class C { constructor() {} a() {} }', options: [{ max: 2 }] },
        // Accessors and static methods count towards the same total.
        { code: 'class C { get x() { return 1; } set x(v: number) {} }', options: [{ max: 2 }] },
        { code: 'class C { static a() {} static b() {} }', options: [{ max: 2 }] },
        // A method spread across overload signatures counts once, via its body.
        {
            code: `class C {
                foo(a: number): number;
                foo(a: string): string;
                foo(a: number | string): number | string { return a; }
                bar() {}
            }`,
            options: [{ max: 2 }],
        },
        // Abstract signatures declare a contract, not a method body, so are not
        // counted.
        { code: 'abstract class C { abstract foo(): void; abstract bar(): void; run() {} }', options: [{ max: 1 }] },
        // Methods on a nested class belong to it, not the class enclosing it.
        { code: 'class Outer { a() {} run() { class Inner { x() {} } } }', options: [{ max: 2 }] },
        // An anonymous class expression within the limit passes.
        { code: 'const C = class { a() {} }; C;', options: [{ max: 1 }] },
        // Test classes gather many methods legitimately: by name, by parent, or
        // by path.
        { code: `class WidgetTest { ${methods(5)} }`, options: [{ max: 1 }] },
        { code: `class C extends TestCase { ${methods(5)} }`, options: [{ max: 1 }] },
        { code: `class C { ${methods(5)} }`, filename: 'widget.test.ts', options: [{ max: 1 }] },
        { code: `class C { ${methods(5)} }`, filename: '/repo/__tests__/widget.ts', options: [{ max: 1 }] },
    ],
    invalid: [
        {
            code: 'class C { a() {} b() {} c() {} }',
            options: [{ max: 2 }],
            errors: [{ messageId: 'tooMany', data: { count: 3, max: 2 } }],
        },
        // The constructor is one of the counted methods.
        {
            code: 'class C { constructor() {} a() {} b() {} }',
            options: [{ max: 2 }],
            errors: [{ messageId: 'tooMany', data: { count: 3, max: 2 } }],
        },
        // Get and set accessors each add to the total.
        {
            code: 'class C { get x() { return 1; } set x(v: number) {} }',
            options: [{ max: 1 }],
            errors: [{ messageId: 'tooMany', data: { count: 2, max: 1 } }],
        },
        // Static methods count alongside instance methods.
        {
            code: 'class C { static a() {} static b() {} }',
            options: [{ max: 1 }],
            errors: [{ messageId: 'tooMany', data: { count: 2, max: 1 } }],
        },
        // Overload signatures do not inflate the count, but the implementation
        // does.
        {
            code: `class C {
                foo(a: number): number;
                foo(a: string): string;
                foo(a: number | string): number | string { return a; }
                bar() {}
                baz() {}
            }`,
            options: [{ max: 2 }],
            errors: [{ messageId: 'tooMany', data: { count: 3, max: 2 } }],
        },
        // A nested class is measured on its own, independently of its
        // enclosure.
        {
            code: 'class Outer { run() { class Inner { a() {} b() {} } } }',
            options: [{ max: 1 }],
            errors: [{ messageId: 'tooMany', data: { count: 2, max: 1 } }],
        },
        // The default limit of twenty is exceeded at twenty-one methods.
        {
            code: `class C { ${methods(21)} }`,
            errors: [{ messageId: 'tooMany', data: { count: 21, max: 20 } }],
        },
        // An anonymous class expression is reported on its class keyword.
        {
            code: 'const C = class { a() {} b() {} }; C;',
            options: [{ max: 1 }],
            errors: [{ messageId: 'tooMany', data: { count: 2, max: 1 } }],
        },
        {
            code: 'abstract class C { a() {} b() {} }',
            options: [{ max: 1 }],
            errors: [{ messageId: 'tooMany', data: { count: 2, max: 1 } }],
        },
        // A non-test class nested in a test class is still enforced.
        {
            code: `class FooTest {
                make() {
                    class Config { a() {} b() {} }
                    return Config;
                }
            }`,
            options: [{ max: 1 }],
            errors: [{ messageId: 'tooMany', data: { count: 2, max: 1 } }],
        },
    ],
});
