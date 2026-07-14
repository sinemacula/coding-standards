import rule from '../no-mutable-static.js';
import { ruleTester } from './tester.js';

// The rule is syntactic and needs no parser services, so the type-free tester also
// stands in for the "no type information available" case.
ruleTester.run('no-mutable-static', rule, {
    valid: [
        'export const MAX = 10;',
        'const value = 1; export { value };',
        'function make() { return 1; } export { make };',
        'class Widget {} export { Widget };',
        "export { anything } from './other';",
        'type T = number; export type { T };',
        // Only exported bindings are in scope; an unexported module let/var stays local.
        'let counter = 0;',
        'var scratch = 0;',
        'namespace N { export const x = 0; }',
        'class C { static readonly COUNT = 0; }',
        'class C { count = 0; }',
        'class C { accessor count = 0; }',
        'class C { static readonly #secret = 1; }',
        'class C { static get value() { return 1; } }',
        'class C { static set value(v: number) {} }',
        'class C { static make(): number { return 1; } }',
        `class C {
            static make(a: number): number;
            static make(a: string): string;
            static make(a: number | string): number | string { return a; }
        }`,
        'class Box<T> { static readonly EMPTY = 0; }',
        'class C { static of<T>(value: T): T { return value; } }',
        'abstract class C { abstract run(): void; abstract value: number; }',
        // Deliberately mutated statics opt out with a doc tag on the field or class.
        'class C { /** @managed-static */ static cache: Record<string, number> = {}; }',
        '/** @managed-static */ class C { static cache = {}; static hits = 0; }',
        // Test classes are exempt, by their own name, their parent's, or the file path.
        'class WidgetTest { static shared = 0; }',
        'class TestCase {} class C extends TestCase { static shared = 0; }',
        { code: 'class C { static shared = 0; }', filename: 'widget.test.ts' },
        { code: 'class C { static shared = 0; }', filename: '/repo/__tests__/widget.ts' },
        // Ambient declarations describe existing shape, not runtime state.
        'export declare let external: number;',
        'declare class C { static count: number; }',
        'declare namespace N { export let x: number; }',
        'class C { declare static count: number; }',
        { code: 'const el = <div />; export const view = el;', filename: 'react.tsx' },
    ],
    invalid: [
        {
            code: 'export let counter = 0;',
            errors: [{ messageId: 'mutableExport', data: { name: 'counter' } }],
        },
        {
            code: 'export var flag = false;',
            errors: [{ messageId: 'mutableExport', data: { name: 'flag' } }],
        },
        {
            code: 'export let a = 1, b = 2;',
            errors: [
                { messageId: 'mutableExport', data: { name: 'a' } },
                { messageId: 'mutableExport', data: { name: 'b' } },
            ],
        },
        {
            code: 'const pair: [number, number] = [1, 2]; export let [first, second] = pair;',
            errors: [
                { messageId: 'mutableExport', data: { name: 'first' } },
                { messageId: 'mutableExport', data: { name: 'second' } },
            ],
        },
        // Indirect specifier exports of a mutable local publish live module state.
        {
            code: 'let counter = 0; export { counter };',
            errors: [{ messageId: 'mutableExport', data: { name: 'counter' } }],
        },
        {
            code: 'let counter = 0; export { counter as default };',
            errors: [{ messageId: 'mutableExport', data: { name: 'counter' } }],
        },
        {
            code: 'var flag = false; export { flag };',
            errors: [{ messageId: 'mutableExport', data: { name: 'flag' } }],
        },
        {
            code: 'namespace N { export let x = 0; }',
            errors: [{ messageId: 'mutableExport', data: { name: 'x' } }],
        },
        {
            code: 'class C { static count = 0; }',
            errors: [{ messageId: 'mutableStatic', data: { name: 'count' } }],
        },
        {
            code: 'class C { static accessor count = 0; }',
            errors: [{ messageId: 'mutableStatic', data: { name: 'count' } }],
        },
        {
            code: 'class C { static config: Record<string, number> = {}; }',
            errors: [{ messageId: 'mutableStatic', data: { name: 'config' } }],
        },
        // A static callable slot is still reassignable state, so it is flagged like
        // any other mutable static rather than exempted as behaviour. Function-typed,
        // union-callable and any-typed slots are all treated the same way.
        {
            code: 'class C { static make = () => 1; }',
            errors: [{ messageId: 'mutableStatic', data: { name: 'make' } }],
        },
        {
            code: 'class C { static handler = function () { return true; }; }',
            errors: [{ messageId: 'mutableStatic', data: { name: 'handler' } }],
        },
        {
            code: 'class C { static handler: Function = () => {}; }',
            errors: [{ messageId: 'mutableStatic', data: { name: 'handler' } }],
        },
        {
            code: 'class C { static cb: (() => void) | number = 1; }',
            errors: [{ messageId: 'mutableStatic', data: { name: 'cb' } }],
        },
        {
            code: 'class C { static data: any = 1; }',
            errors: [{ messageId: 'mutableStatic', data: { name: 'data' } }],
        },
        // A static that is only read is still flagged; the check is write-insensitive.
        {
            code: 'const defaults = { a: 1 }; class C { static settings = defaults; '
                + 'static read() { return C.settings; } }',
            errors: [{ messageId: 'mutableStatic', data: { name: 'settings' } }],
        },
        {
            code: 'class C { static #instances = 0; }',
            errors: [{ messageId: 'mutableStatic', data: { name: '#instances' } }],
        },
        {
            code: "const key = 'k'; class C { static [key] = 0; }",
            errors: [{ messageId: 'mutableStatic', data: { name: '[key]' } }],
        },
        {
            code: 'class Box<T> { static count = 0; }',
            errors: [{ messageId: 'mutableStatic', data: { name: 'count' } }],
        },
        {
            code: 'declare const track: any; class C { @track static count = 0; }',
            errors: [{ messageId: 'mutableStatic', data: { name: 'count' } }],
        },
        {
            code: 'abstract class C { static count = 0; }',
            errors: [{ messageId: 'mutableStatic', data: { name: 'count' } }],
        },
        // A near-miss tag does not opt out; the boundary is exact.
        {
            code: 'class C { /** @managed-statics */ static count = 0; }',
            errors: [{ messageId: 'mutableStatic', data: { name: 'count' } }],
        },
        {
            code: 'export let node = <div />;',
            filename: 'react.tsx',
            errors: [{ messageId: 'mutableExport', data: { name: 'node' } }],
        },
    ],
});
