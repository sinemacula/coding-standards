/**
 * Tests for the boolean-method-name rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import rule from '../boolean-method-name.js';
import { ruleTester, typedRuleTester } from './tester.js';

typedRuleTester.run('boolean-method-name', rule, {
    valid: [
        // Copular and modal prefixes.
        'function isValid(): boolean { return true; }',
        'function hasItems(): boolean { return true; }',
        'function canRun(): boolean { return true; }',
        'function shouldRetry(): boolean { return true; }',
        'function willExpire(): boolean { return true; }',
        'function wasSaved(): boolean { return true; }',

        // Third-person and past-tense verbs, and idiomatic predicates.
        'function permits(): boolean { return true; }',
        'function succeeded(): boolean { return true; }',
        'function successful(): boolean { return true; }',

        // Imperative command verbs may return a result bool.
        'function validate(): boolean { return true; }',
        'function execute(): boolean { return true; }',
        'function submit(): boolean { return true; }',
        'function confirm(): boolean { return true; }',
        'function copyToClipboard(): boolean { return true; }',
        'function attemptLogin(): boolean { return true; }',
        'function rotateKeys(): boolean { return true; }',

        // An override's name is set by its parent.
        'class Base { /** @imperative */ valid(): boolean { return true; } } class C extends Base { override valid(): boolean { return false; } }',
        'abstract class Base { /** @imperative */ abstract valid(): boolean; } class C extends Base { override valid(): boolean { return true; } }',
        'abstract class Base { /** @imperative */ abstract valid(): boolean; } abstract class C extends Base { abstract override valid(): boolean; }',
        'class Base { /** @imperative */ valid = (): boolean => true; } class C extends Base { override valid = (): boolean => false; }',

        // on* event handlers are not predicates.
        'const o = { onUnauthorized(): boolean { return true; } };',
        'class C { onError(): boolean { return true; } }',

        // A method named after a primitive type is a typed accessor.
        'class C { boolean(): boolean { return true; } }',
        'interface Env { boolean(key: string): boolean; }',

        'function isReady() { return 1 > 0; }',

        // Non-boolean returns are out of scope.
        'function widget(): string { return ""; }',
        'function build(): number { return 1; }',

        'function isThing<T>(x: T): boolean { return true; }',

        'async function isReady(): Promise<boolean> { return true; }',
        'async function validate(): Promise<boolean> { return true; }',

        // Type-predicate guards are predicates by structure.
        'function nonNullable<T>(x: T): x is NonNullable<T> { return x != null; }',
        'function present<T>(x: T | null): x is T { return x !== null; }',

        // Accessors, constructor, computed and magic names are exempt.
        'class C { get active(): boolean { return true; } }',
        'class C { set active(v: boolean) {} }',
        'class C { constructor() {} }',
        'class C { ["ready"](): boolean { return true; } }',
        'function __internal(): boolean { return true; }',

        'class C { isValid(): boolean { return true; } }',
        'class C { hasItems(x: string): boolean; hasItems(x: number): boolean; '
        + 'hasItems(x: any): boolean { return true; } }',
        'abstract class A { abstract isValid(): boolean; }',

        'class C { isValid = (): boolean => true; }',
        'const o = { isReady(): boolean { return true; } };',
        'const o = { isReady: (): boolean => true };',
        'const o = { get active(): boolean { return true; } };',
        'const isReady = (): boolean => true;',
        'const isReady = function (): boolean { return true; };',

        'interface Foo { isValid(): boolean; }',
        'type Foo = { isValid(): boolean };',

        'declare function isReady(): boolean;',
        'declare namespace N { function isReady(): boolean; }',
        'declare class C { isValid(): boolean; }',

        // The @imperative opt-out, including behind a decorator.
        '/** @imperative */\nfunction frobnicate(): boolean { return true; }',
        '/** @imperative */\nconst frobnicate = (): boolean => true;',
        'const dec = (_v: any, _c: any) => {};\nclass C {\n  /** @imperative */\n'
        + '  @dec\n  frobnicate(): boolean { return true; }\n}',
        'const dec = (_v: any, _c: any) => {};\nclass C {\n  @dec\n'
        + '  /** @imperative */\n  frobnicate(): boolean { return true; }\n}',

        {
            code:    'function frobnicate(): boolean { return true; }',
            options: [{ additionalCommandVerbs: ['frobnicate'] }],
        },
        {
            code:    'function wibble(): boolean { return true; }',
            options: [{ additionalPrefixes: ['wibble'] }],
        },

        { code: 'function panel() { return <div />; }', filename: 'react.tsx' },
    ],
    invalid: [
        {
            code:   'function frobnicate(): boolean { return true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'frobnicate' } }],
        },
        {
            // An inferred boolean return.
            code:   'function frobnicate() { return 1 > 0; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'frobnicate' } }],
        },
        {
            code:   'async function frobnicate(): Promise<boolean> { return true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'frobnicate' } }],
        },
        {
            code:   'async function frobnicate() { return true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'frobnicate' } }],
        },
        {
            code:   'async function widget(): Promise<boolean | undefined> { return true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'widget' } }],
        },
        {
            code:   'class C { valid(): boolean { return true; } }',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            // The parent's own declaration is still checked.
            code:   'class Base { valid(): boolean { return true; } } class C extends Base { override valid(): boolean { return false; } }',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            code:   'class C { valid = (): boolean => true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            code:   'const o = { valid(): boolean { return true; } };',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            code:   'const o = { valid: (): boolean => true };',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            code:   'const valid = (): boolean => true;',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            code:   'const valid = function (): boolean { return true; };',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            code:   'interface Foo { valid(): boolean; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            code:   'type Foo = { valid(): boolean };',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            code:   'class C { "valid"(): boolean { return true; } }',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            code:   'const o = { "valid"(): boolean { return true; } };',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            code:   'interface Foo { "valid"(): boolean; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            // as/satisfies wrappers are unwrapped.
            code:   'const valid = ((): boolean => true) satisfies () => boolean;',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            code:   'const valid = (() => true) as () => boolean;',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            code:   'abstract class A { abstract active(): boolean; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'active' } }],
        },
        {
            code:   'function widget<T>(x: T): boolean { return true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'widget' } }],
        },
        {
            code:   'declare function widget(): boolean;',
            errors: [{ messageId: 'notPredicate', data: { name: 'widget' } }],
        },
        {
            // A function nested in an ambient namespace is a real declaration.
            code:   'declare namespace N { function widget(): boolean; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'widget' } }],
        },
        {
            // A bodiless member of an ambient class is a real declaration.
            code:   'declare class C { valid(): boolean; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            code:   'function widget(): boolean | undefined { return true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'widget' } }],
        },
        {
            // Overloads report once, on the implementation.
            code:   'function widget(x: string): boolean;\n'
                + 'function widget(x: number): boolean;\n'
                + 'function widget(x: any): boolean { return true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'widget' } }],
        },
        {
            code:   'class C { widget(x: string): boolean; widget(x: any): boolean { return true; } }',
            errors: [{ messageId: 'notPredicate', data: { name: 'widget' } }],
        },
        {
            code:   'const dec = (_v: any, _c: any) => {};\nclass C {\n  @dec\n  valid(): boolean { return true; }\n}',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            // @imperative in prose is not a tag.
            code:   '/** handles @imperative flows */\nfunction valid(): boolean { return true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            code:     'function widget(): boolean { return true; }',
            filename: 'react.tsx',
            errors:   [{ messageId: 'notPredicate', data: { name: 'widget' } }],
        },
        {
            // `on` without a following capital is not an event handler.
            code:   'function online(): boolean { return true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'online' } }],
        },
        {
            // Only the exact primitive-type name is a typed accessor.
            code:   'class C { booleanFlag(): boolean { return true; } }',
            errors: [{ messageId: 'notPredicate', data: { name: 'booleanFlag' } }],
        },
    ],
});

// Without type information the rule is a no-op rather than a crash.
ruleTester.run('boolean-method-name (no type information)', rule, {
    valid: [
        'function frobnicate(): boolean { return true; }',
        'class C { valid(): boolean { return true; } }',
    ],
    invalid: [],
});
