/**
 * Tests for the no-interface-prefix rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import rule from '../no-interface-prefix.js';
import { ruleTester } from './tester.js';

ruleTester.run('no-interface-prefix', rule, {
    valid: [
        // Plain, correctly named declarations.
        'interface UserRepository {}',
        'interface Item {}',
        'type UserId = string;',
        'type Id = number;',

        // "I" not followed by an uppercase letter is not the prefix.
        'interface I {}',
        'interface Interface {}',
        'interface Iterable {}',

        // Generic parameters are not the declaration name, so they are ignored.
        'interface Box<TItem> {}',
        'type Container<TValue> = TValue[];',
        'interface Registry<IItem> { value: IItem; }',

        // Overload signatures and "I"-prefixed members are not declaration names.
        'interface Repository { find(id: number): void; find(id: string): void; ILoad(): void; ICount: number; }',

        // The rule targets interfaces and type aliases only, never other kinds.
        '@sealed class IController {}',
        'abstract class Base { abstract IRun(): void; get IValue(): number { return 1; } ["IKey"](): void {} }',
        'enum IStatus { Active }',
        'function IProcess() {}',
        'const IThing = 1;',
        'namespace IUtils {}',

        // References to an externally-owned "I"-prefixed type are never the
        // declaration name, so nothing that merely mentions one is flagged.
        'import { IFoo } from "./types";',
        'import type { IFoo } from "./types";',
        'export { IFoo } from "./types";',
        'export type { IFoo } from "./types";',
        'let value: IFoo;',
        'type Wrapped = IFoo;',
        'interface Repository extends IFoo {}',

        // Augmenting a string-named external module patches a type we cannot rename.
        'declare module "pkg" { interface INested {} }',
        'declare module "some-lib" { interface IConfig { extra: string; } }',
        'declare module "m" { type IBaz = number; }',

        // Global augmentation patches ambient types we do not own.
        'declare global { interface IWindow {} }',
        'declare global { interface IToastOptions {} }',
    ],
    invalid: [
        {
            code: 'interface IUserRepository {}',
            errors: [
                { messageId: 'prefixed', data: { kind: 'Interface', name: 'IUserRepository' }, line: 1, column: 11 },
            ],
        },
        {
            code: 'type IFoo = string;',
            errors: [{ messageId: 'prefixed', data: { kind: 'Type', name: 'IFoo' }, line: 1, column: 6 }],
        },

        // Minimal match: "I" plus a single uppercase letter.
        {
            code: 'interface IO {}',
            errors: [{ messageId: 'prefixed', data: { kind: 'Interface', name: 'IO' } }],
        },

        // Generics do not change the declaration name.
        {
            code: 'interface IFoo<T> {}',
            errors: [{ messageId: 'prefixed', data: { kind: 'Interface', name: 'IFoo' } }],
        },
        {
            code: 'type IHandler<T> = (value: T) => void;',
            errors: [{ messageId: 'prefixed', data: { kind: 'Type', name: 'IHandler' } }],
        },

        // Ambient declarations are still flagged.
        {
            code: 'declare interface IBar {}',
            errors: [{ messageId: 'prefixed', data: { kind: 'Interface', name: 'IBar' } }],
        },
        {
            code: 'declare type IBaz = number;',
            errors: [{ messageId: 'prefixed', data: { kind: 'Type', name: 'IBaz' } }],
        },

        // Export-default interfaces still carry a renamable name.
        {
            code: 'export default interface IThing {}',
            errors: [{ messageId: 'prefixed', data: { kind: 'Interface', name: 'IThing' } }],
        },

        // A namespace or internal module you own does not exempt its members.
        {
            code: 'namespace Owned { interface INested {} }',
            errors: [{ messageId: 'prefixed', data: { kind: 'Interface', name: 'INested' } }],
        },
        {
            code: 'namespace Owned { type IFoo = string; }',
            errors: [{ messageId: 'prefixed', data: { kind: 'Type', name: 'IFoo' } }],
        },
        {
            code: 'module Owned { type IBaz = number; }',
            errors: [{ messageId: 'prefixed', data: { kind: 'Type', name: 'IBaz' } }],
        },

        // Declaration files carry no exemption.
        {
            code: 'interface IConfig {}',
            filename: 'globals.d.ts',
            errors: [{ messageId: 'prefixed', data: { kind: 'Interface', name: 'IConfig' } }],
        },

        // Exports are still flagged.
        {
            code: 'export interface IService {}',
            errors: [{ messageId: 'prefixed', data: { kind: 'Interface', name: 'IService' } }],
        },
        {
            code: 'export type IResult = string;',
            errors: [{ messageId: 'prefixed', data: { kind: 'Type', name: 'IResult' } }],
        },

        // Merged declarations are reported once each.
        {
            code: 'interface IFoo {} interface IFoo {}',
            errors: [
                { messageId: 'prefixed', data: { kind: 'Interface', name: 'IFoo' } },
                { messageId: 'prefixed', data: { kind: 'Interface', name: 'IFoo' } },
            ],
        },
    ],
});
