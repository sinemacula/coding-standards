/**
 * Tests for the valid-enum-member-name rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import rule from '../valid-enum-member-name.js';
import { ruleTester } from './tester.js';

ruleTester.run('valid-enum-member-name', rule, {
    valid: [
        'enum Color { RED, GREEN, BLUE }',
        // Single letter, digits and underscores are permitted.
        'enum Grade { A, B, C }',
        'enum HttpStatus { OK_200, NOT_FOUND_404 }',
        'enum Flag { RED_ }',
        'enum Level { LOW = 1, HIGH = 2 }',
        'enum Suit { CLUB = "club", HEART = "heart" }',
        'enum Weird { "RED" = 1, "GREEN_LIGHT" = 2 }',
        // A string literal is checked by its decoded value, not its raw text.
        'enum Escaped { "R\\u0045D" = 1 }',
        'const enum Mode { ON, OFF }',
        'declare enum Ambient { YES, NO }',
        { code: 'enum Ambient { YES, NO }', filename: 'types.d.ts' },
        { code: 'enum Theme { LIGHT, DARK }', filename: 'react.tsx' },
        // Non-enum constructs; `as const` pseudo-enums are a known gap.
        'const config = { red: 1, greenLight: 2 };',
        'function grade(n: number) { switch (n) { case 1: return "a"; default: return "b"; } }',
        'interface Options { red: number; greenLight: string; }',
        'class Box { private width = 0; get size() { return this.width; } set size(v: number) { this.width = v; } }',
        'abstract class Shape { abstract area(): number; }',
        'function identity<T>(value: T): T { return value; }',
        'class Container<T> { value?: T; }',
        'function overloaded(x: number): number;\n'
        + 'function overloaded(x: string): string;\n'
        + 'function overloaded(x: unknown) { return x; }',
        'const key = "x";\nconst dynamic = { [key]: 1 };',
        '@sealed\nclass Decorated { private handler = () => {}; }',
    ],
    invalid: [
        {
            code: 'enum Color { red }',
            errors: [{ messageId: 'notUpperSnakeCase', data: { name: 'red' } }],
        },
        {
            code: 'enum Color { Red }',
            errors: [{ messageId: 'notUpperSnakeCase', data: { name: 'Red' } }],
        },
        {
            code: 'enum Size { smallSize }',
            errors: [{ messageId: 'notUpperSnakeCase', data: { name: 'smallSize' } }],
        },
        {
            code: 'enum Leading { _RED }',
            errors: [{ messageId: 'notUpperSnakeCase', data: { name: '_RED' } }],
        },
        {
            code: 'enum Mixed { RED, green, blue }',
            errors: [
                { messageId: 'notUpperSnakeCase', data: { name: 'green' } },
                { messageId: 'notUpperSnakeCase', data: { name: 'blue' } },
            ],
        },
        {
            code: 'enum Weird { "foo-bar" = 1 }',
            errors: [{ messageId: 'notUpperSnakeCase', data: { name: 'foo-bar' } }],
        },
        {
            code: 'enum Weird { "1RED" = 1 }',
            errors: [{ messageId: 'notUpperSnakeCase', data: { name: '1RED' } }],
        },
        {
            code: 'enum Weird { "" = 1 }',
            errors: [{ messageId: 'notUpperSnakeCase', data: { name: '' } }],
        },
        {
            code: 'enum Weird { "redValue" = 1 }',
            errors: [{ messageId: 'notUpperSnakeCase', data: { name: 'redValue' } }],
        },
        {
            // The pattern is ASCII-only.
            code: 'enum Accent { Ä }',
            errors: [{ messageId: 'notUpperSnakeCase', data: { name: 'Ä' } }],
        },
        {
            code: 'enum Dollar { $VALUE }',
            errors: [{ messageId: 'notUpperSnakeCase', data: { name: '$VALUE' } }],
        },
        {
            code: 'const enum Mode { on, off }',
            errors: [
                { messageId: 'notUpperSnakeCase', data: { name: 'on' } },
                { messageId: 'notUpperSnakeCase', data: { name: 'off' } },
            ],
        },
        {
            code: 'declare enum Ambient { yes }',
            errors: [{ messageId: 'notUpperSnakeCase', data: { name: 'yes' } }],
        },
        {
            code: 'enum Ambient { yes }',
            filename: 'types.d.ts',
            errors: [{ messageId: 'notUpperSnakeCase', data: { name: 'yes' } }],
        },
        {
            code: 'enum Theme { light, dark }',
            filename: 'react.tsx',
            errors: [
                { messageId: 'notUpperSnakeCase', data: { name: 'light' } },
                { messageId: 'notUpperSnakeCase', data: { name: 'dark' } },
            ],
        },
    ],
});
