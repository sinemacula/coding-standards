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
        { code: 'class C { a() {} b() {} }', options: [{ max: 2 }] },
        { code: 'class C { a() {} }', options: [{ max: 2 }] },
        'class C {}',
        `class C { ${methods(20)} }`,
        { code: 'class C { constructor() {} a() {} }', options: [{ max: 2 }] },
        { code: 'class C { get x() { return 1; } set x(v: number) {} }', options: [{ max: 2 }] },
        { code: 'class C { static a() {} static b() {} }', options: [{ max: 2 }] },
        // Overloads count once, via the implementation.
        {
            code: `class C {
                foo(a: number): number;
                foo(a: string): string;
                foo(a: number | string): number | string { return a; }
                bar() {}
            }`,
            options: [{ max: 2 }],
        },
        // Abstract signatures are not counted.
        { code: 'abstract class C { abstract foo(): void; abstract bar(): void; run() {} }', options: [{ max: 1 }] },
        { code: 'class Outer { a() {} run() { class Inner { x() {} } } }', options: [{ max: 2 }] },
        { code: 'const C = class { a() {} }; C;', options: [{ max: 1 }] },
        // Test classes are exempt by name, parent or path.
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
        {
            code: 'class C { constructor() {} a() {} b() {} }',
            options: [{ max: 2 }],
            errors: [{ messageId: 'tooMany', data: { count: 3, max: 2 } }],
        },
        {
            code: 'class C { get x() { return 1; } set x(v: number) {} }',
            options: [{ max: 1 }],
            errors: [{ messageId: 'tooMany', data: { count: 2, max: 1 } }],
        },
        {
            code: 'class C { static a() {} static b() {} }',
            options: [{ max: 1 }],
            errors: [{ messageId: 'tooMany', data: { count: 2, max: 1 } }],
        },
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
        {
            code: 'class Outer { run() { class Inner { a() {} b() {} } } }',
            options: [{ max: 1 }],
            errors: [{ messageId: 'tooMany', data: { count: 2, max: 1 } }],
        },
        {
            code: `class C { ${methods(21)} }`,
            errors: [{ messageId: 'tooMany', data: { count: 21, max: 20 } }],
        },
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
