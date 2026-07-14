import rule from '../require-readonly-public-property.js';
import { ruleTester } from './tester.js';

ruleTester.run('require-readonly-public-property', rule, {
    valid: [
        // A public property is fine once it is readonly.
        'class C { public readonly x = 1; }',
        'class C { readonly x = 1; }',
        // Non-public properties are outside the mandate.
        'class C { private x = 1; }',
        'class C { protected x = 1; }',
        'class C { #x = 1; }',
        // Static state is left to a separate concern.
        'class C { static x = 1; }',
        'class C { public static x = 1; }',
        // Constructor-promoted properties, readonly forms.
        'class C { constructor(public readonly x: number) {} }',
        'class C { constructor(readonly x: number) {} }',
        'class C { constructor(private x: number) {} }',
        'class C { constructor(protected x: number) {} }',
        // A plain constructor parameter is not a property at all.
        'class C { constructor(x: number) {} }',
        // Getter/setter accessors are methods, not data properties.
        'class C { get x() { return 1; } set x(v: number) {} }',
        // Non-public and static auto-accessors stay outside the mandate.
        'class C { private accessor x = 1; }',
        'class C { protected accessor x = 1; }',
        'class C { static accessor x = 1; }',
        'class C { accessor #x = 1; }',
        // Generics on a readonly promoted property.
        'class Box<T> { constructor(public readonly value: T) {} }',
        // Constructor overloads with an immutable implementation.
        `class C {
            constructor(x: number);
            constructor(x: string);
            constructor(public readonly x: number | string) {}
        }`,
        // Computed and decorated public members, readonly forms.
        'class C { readonly ["dynamic"] = 1; }',
        'class C { @dec public readonly x = 1; }',
        // An index signature is a structural type, not a mutable field.
        'class C { [k: string]: number; }',
        // Abstract public members may declare a readonly contract.
        'abstract class C { public abstract readonly x: number; }',
        // Ambient declarations describe external shapes; not enforced.
        'declare class C { public x: number; }',
        'declare namespace N { class C { public x: number; } }',
        'declare global { class C { public x: number; } }',
        'class C { declare public x: number; }',
        { code: 'class C { x: number; }', filename: 'globals.d.ts' },
        { code: 'class C { x: number; }', filename: 'globals.d.mts' },
        { code: 'class C { x: number; }', filename: 'globals.d.cts' },
        { code: 'declare class C { public x: number; }', filename: 'types.d.ts' },
        // Configured parent classes exempt their subclasses.
        {
            code: 'class Product extends Model { public name = ""; }',
            options: [{ ignoredParentClasses: ['Model'] }],
        },
        // A qualified parent is matched on its final name segment.
        {
            code: 'class Product extends ns.Model { public name = ""; }',
            options: [{ ignoredParentClasses: ['Model'] }],
        },
        // Test fixtures are exempt: by class name, parent, or file location.
        'class UserTest { public spy = 1; }',
        'class Fake extends TestCase { public calls = 0; }',
        { code: 'class Fake { public calls = 0; }', filename: 'src/tests/Fake.ts' },
        { code: 'class Fake { public calls = 0; }', filename: 'Fake.spec.ts' },
        { code: 'class Fake { public calls = 0; }', filename: 'Payment.test.ts' },
        { code: 'class Fake { public calls = 0; }', filename: 'src/__tests__/Fake.ts' },
    ],
    invalid: [
        {
            code: 'class C { public x = 1; }',
            errors: [{ messageId: 'mutable', data: { name: 'x' } }],
        },
        {
            code: 'class C { x = 1; }',
            errors: [{ messageId: 'mutable', data: { name: 'x' } }],
        },
        {
            code: 'class C { public x: number; }',
            errors: [{ messageId: 'mutable', data: { name: 'x' } }],
        },
        {
            code: 'class C { constructor(public x: number) {} }',
            errors: [{ messageId: 'mutable', data: { name: 'x' } }],
        },
        {
            code: 'class C { constructor(public x: number = 1) {} }',
            errors: [{ messageId: 'mutable', data: { name: 'x' } }],
        },
        {
            code: 'class Box<T> { public value: T; }',
            errors: [{ messageId: 'mutable', data: { name: 'value' } }],
        },
        {
            // Only the implementation carries the promoted property.
            code: `class C {
                constructor(x: number);
                constructor(public x: number) {}
            }`,
            errors: [{ messageId: 'mutable', data: { name: 'x' } }],
        },
        {
            code: 'class C { ["dynamic"] = 1; }',
            errors: [{ messageId: 'mutable', data: { name: 'dynamic' } }],
        },
        {
            // A decorator must not mask the mutable property beneath it.
            code: 'class C { @dec public x = 1; }',
            errors: [{ messageId: 'mutable', data: { name: 'x' } }],
        },
        {
            code: 'class C { constructor(@Inject() public x: number) {} }',
            errors: [{ messageId: 'mutable', data: { name: 'x' } }],
        },
        {
            code: 'abstract class C { public abstract x: number; }',
            errors: [{ messageId: 'mutable', data: { name: 'x' } }],
        },
        {
            // The ignored-parent option is specific to the named parent.
            code: 'class Product extends Other { public name = ""; }',
            options: [{ ignoredParentClasses: ['Model'] }],
            errors: [{ messageId: 'mutable', data: { name: 'name' } }],
        },
        {
            // A bare auto-accessor is public and inherently mutable.
            code: 'class C { accessor x = 1; }',
            errors: [{ messageId: 'accessor', data: { name: 'x' } }],
        },
        {
            // The escape hatch: a public auto-accessor has no readonly form.
            code: 'class C { public accessor x = 1; }',
            errors: [{ messageId: 'accessor', data: { name: 'x' } }],
        },
        {
            code: 'abstract class C { abstract accessor x: number; }',
            errors: [{ messageId: 'accessor', data: { name: 'x' } }],
        },
        {
            // Each promoted property is reported on its own.
            code: 'class C { constructor(public a: number, public b: number) {} }',
            errors: [
                { messageId: 'mutable', data: { name: 'a' } },
                { messageId: 'mutable', data: { name: 'b' } },
            ],
        },
        {
            // A non-test class nested in a test class is still enforced.
            code: `class FooTest {
                make() {
                    class Config { public value = 1; }
                    return Config;
                }
            }`,
            errors: [{ messageId: 'mutable', data: { name: 'value' } }],
        },
        {
            // A non-literal computed key reports its written text.
            code: 'class C { [Symbol.iterator] = 1; }',
            errors: [{ messageId: 'mutable', data: { name: 'Symbol.iterator' } }],
        },
        {
            // A mixin-produced base cannot be name-matched, so it stays enforced.
            code: 'class C extends mixin(Base) { public x = 1; }',
            options: [{ ignoredParentClasses: ['Base'] }],
            errors: [{ messageId: 'mutable', data: { name: 'x' } }],
        },
        {
            code: 'class App { public count = 0; render() { return <div />; } }',
            filename: 'react.tsx',
            errors: [{ messageId: 'mutable', data: { name: 'count' } }],
        },
    ],
});
