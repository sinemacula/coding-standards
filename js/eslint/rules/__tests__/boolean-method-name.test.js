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

        // Third-person / past-tense verbs and idiomatic predicates.
        'function permits(): boolean { return true; }',
        'function succeeded(): boolean { return true; }',
        'function successful(): boolean { return true; }',

        // Imperative command verbs may return a result bool.
        'function validate(): boolean { return true; }',
        'function execute(): boolean { return true; }',

        // Inferred boolean return with a predicate name.
        'function isReady() { return 1 > 0; }',

        // Non-boolean returns are out of scope regardless of the name.
        'function widget(): string { return ""; }',
        'function build(): number { return 1; }',

        // Generics resolve to a concrete boolean return.
        'function isThing<T>(x: T): boolean { return true; }',

        // Awaited booleans read as predicates when named as one.
        'async function isReady(): Promise<boolean> { return true; }',
        'async function validate(): Promise<boolean> { return true; }',

        // Type-predicate guards are predicates by structure; the descriptive name
        // need not start with is/has.
        'function nonNullable<T>(x: T): x is NonNullable<T> { return x != null; }',
        'function present<T>(x: T | null): x is T { return x !== null; }',

        // Accessors, constructor, computed and magic names are exempt.
        'class C { get active(): boolean { return true; } }',
        'class C { set active(v: boolean) {} }',
        'class C { constructor() {} }',
        'class C { ["ready"](): boolean { return true; } }',
        'function __internal(): boolean { return true; }',

        // Well-named methods, overloads and abstract members.
        'class C { isValid(): boolean { return true; } }',
        'class C { hasItems(x: string): boolean; hasItems(x: number): boolean; hasItems(x: any): boolean { return true; } }',
        'abstract class A { abstract isValid(): boolean; }',

        // Arrow-bound class fields, object methods and const-bound functions.
        'class C { isValid = (): boolean => true; }',
        'const o = { isReady(): boolean { return true; } };',
        'const o = { isReady: (): boolean => true };',
        'const o = { get active(): boolean { return true; } };',
        'const isReady = (): boolean => true;',
        'const isReady = function (): boolean { return true; };',

        // Interface and type-literal signatures.
        'interface Foo { isValid(): boolean; }',
        'type Foo = { isValid(): boolean };',

        // Ambient declarations, including nested and class members, with predicate names.
        'declare function isReady(): boolean;',
        'declare namespace N { function isReady(): boolean; }',
        'declare class C { isValid(): boolean; }',

        // Opt-out via an @imperative docblock, including through a decorator and on
        // a const binding.
        '/** @imperative */\nfunction frobnicate(): boolean { return true; }',
        '/** @imperative */\nconst frobnicate = (): boolean => true;',
        'const dec = (_v: any, _c: any) => {};\nclass C {\n  /** @imperative */\n  @dec\n  frobnicate(): boolean { return true; }\n}',
        'const dec = (_v: any, _c: any) => {};\nclass C {\n  @dec\n  /** @imperative */\n  frobnicate(): boolean { return true; }\n}',

        // A component in a TSX file returns markup, not boolean.
        { code: 'function panel() { return <div />; }', filename: 'react.tsx' },
    ],
    invalid: [
        {
            code:   'function frobnicate(): boolean { return true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'frobnicate' } }],
        },
        {
            // Inferred boolean return, proving the rule reads type information.
            code:   'function frobnicate() { return 1 > 0; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'frobnicate' } }],
        },
        {
            // An awaited boolean is still a predicate concern.
            code:   'async function frobnicate(): Promise<boolean> { return true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'frobnicate' } }],
        },
        {
            // Inferred and awaited together resolve to boolean.
            code:   'async function frobnicate() { return true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'frobnicate' } }],
        },
        {
            // An awaited nullable boolean keeps its predicate tail.
            code:   'async function widget(): Promise<boolean | undefined> { return true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'widget' } }],
        },
        {
            code:   'class C { valid(): boolean { return true; } }',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            // Arrow-bound class fields are methods too.
            code:   'class C { valid = (): boolean => true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            // Object shorthand methods and arrow properties are inspected.
            code:   'const o = { valid(): boolean { return true; } };',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            code:   'const o = { valid: (): boolean => true };',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            // Const-bound arrow and function expressions are inspected.
            code:   'const valid = (): boolean => true;',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            code:   'const valid = function (): boolean { return true; };',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            // Interface and type-literal method signatures are inspected.
            code:   'interface Foo { valid(): boolean; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            code:   'type Foo = { valid(): boolean };',
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
            // A nullable boolean still reads as a predicate concern.
            code:   'function widget(): boolean | undefined { return true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'widget' } }],
        },
        {
            // Function overloads report once, on the implementation.
            code:   'function widget(x: string): boolean;\nfunction widget(x: number): boolean;\nfunction widget(x: any): boolean { return true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'widget' } }],
        },
        {
            // Method overloads report once, on the implementation.
            code:   'class C { widget(x: string): boolean; widget(x: any): boolean { return true; } }',
            errors: [{ messageId: 'notPredicate', data: { name: 'widget' } }],
        },
        {
            // Decorators do not exempt a mis-named boolean method.
            code:   'const dec = (_v: any, _c: any) => {};\nclass C {\n  @dec\n  valid(): boolean { return true; }\n}',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            // @imperative buried in prose is not a tag and does not exempt.
            code:   '/** handles @imperative flows */\nfunction valid(): boolean { return true; }',
            errors: [{ messageId: 'notPredicate', data: { name: 'valid' } }],
        },
        {
            // The rule still bites inside a TSX file.
            code:     'function widget(): boolean { return true; }',
            filename: 'react.tsx',
            errors:   [{ messageId: 'notPredicate', data: { name: 'widget' } }],
        },
    ],
});

// Without a tsconfig program the rule cannot resolve return types, so it must
// degrade to a no-op rather than throw on files outside the consumer's project.
ruleTester.run('boolean-method-name (no type information)', rule, {
    valid: [
        'function frobnicate(): boolean { return true; }',
        'class C { valid(): boolean { return true; } }',
    ],
    invalid: [],
});
