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
        // Plain SCREAMING_SNAKE_CASE members.
        'enum Color { RED, GREEN, BLUE }',
        // Single letter, digits and underscores are permitted.
        'enum Grade { A, B, C }',
        'enum HttpStatus { OK_200, NOT_FOUND_404 }',
        'enum Flag { RED_ }',
        // Explicit initialisers do not change the member name.
        'enum Level { LOW = 1, HIGH = 2 }',
        'enum Suit { CLUB = "club", HEART = "heart" }',
        // String-literal member names that resolve to SCREAMING_SNAKE_CASE.
        'enum Weird { "RED" = 1, "GREEN_LIGHT" = 2 }',
        // A string literal is checked by its resolved value, so an escaped name
        // that decodes to SCREAMING_SNAKE_CASE passes even though the raw
        // source text does not match the pattern.
        'enum Escaped { "R\\u0045D" = 1 }',
        // const enums and ambient declarations are still checked.
        'const enum Mode { ON, OFF }',
        'declare enum Ambient { YES, NO }',
        // Ambient members in a declaration file.
        { code: 'enum Ambient { YES, NO }', filename: 'types.d.ts' },
        // Enum inside a TSX file.
        { code: 'enum Theme { LIGHT, DARK }', filename: 'react.tsx' },
        // The selector must not fire on non-enum constructs. Object-literal "as
        // const" pseudo-enum members are a documented gap.
        'const config = { red: 1, greenLight: 2 };',
        // A switch `case` is a SwitchCase node, never an enum member.
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
            // An empty string-literal name never matches the pattern.
            code: 'enum Weird { "" = 1 }',
            errors: [{ messageId: 'notUpperSnakeCase', data: { name: '' } }],
        },
        {
            // A string-literal name that is a valid identifier in the wrong
            // case.
            code: 'enum Weird { "redValue" = 1 }',
            errors: [{ messageId: 'notUpperSnakeCase', data: { name: 'redValue' } }],
        },
        {
            // The pattern is ASCII-only, so a unicode uppercase letter is
            // flagged.
            code: 'enum Accent { Ä }',
            errors: [{ messageId: 'notUpperSnakeCase', data: { name: 'Ä' } }],
        },
        {
            // A dollar-prefixed identifier does not start with an ASCII A-Z.
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
