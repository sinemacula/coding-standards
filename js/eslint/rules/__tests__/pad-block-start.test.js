/**
 * Tests for the pad-block-start rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import rule from '../pad-block-start.js';
import { ruleTester } from './tester.js';

ruleTester.run('pad-block-start', rule, {
    valid: [
        // An interface body opening with a blank line.
        'interface I {\n\n    a: number;\n}',
        // A class body opening with a blank line.
        'class C {\n\n    a = 1;\n}',
        // An enum body opening with a blank line.
        'enum E {\n\n    A,\n}',
        // The blank line falls above a first member's doc comment.
        'interface I {\n\n    /** A value. */\n    a: number;\n}',
        // An empty body has no member to separate.
        'interface I {}',
        'class C {}',
        // A one-line body carries no member to stand off from the brace.
        'interface I { a: number; }',
        'class C { a = 1; }',
        // A nested body is governed on its own terms, each already padded.
        'class C {\n\n    m() {\n\n        class D {\n\n            a = 1;\n        }\n    }\n}',
    ],
    invalid: [
        {
            // An interface whose first member crowds the opening brace.
            code: 'interface I {\n    a: number;\n}',
            output: 'interface I {\n\n    a: number;\n}',
            errors: [{ messageId: 'missing' }],
        },
        {
            // A class whose first member crowds the opening brace.
            code: 'class C {\n    a = 1;\n}',
            output: 'class C {\n\n    a = 1;\n}',
            errors: [{ messageId: 'missing' }],
        },
        {
            // An enum whose first member crowds the opening brace.
            code: 'enum E {\n    A,\n}',
            output: 'enum E {\n\n    A,\n}',
            errors: [{ messageId: 'missing' }],
        },
        {
            // The blank line is inserted above the first member's doc comment.
            code: 'interface I {\n    /** A value. */\n    a: number;\n}',
            output: 'interface I {\n\n    /** A value. */\n    a: number;\n}',
            errors: [{ messageId: 'missing' }],
        },
        {
            // More than one blank line collapses to exactly one.
            code: 'class C {\n\n\n    a = 1;\n}',
            output: 'class C {\n\n    a = 1;\n}',
            errors: [{ messageId: 'missing' }],
        },
        {
            // Indentation of the first member is preserved by the fix.
            code: 'class C {\n        a = 1;\n}',
            output: 'class C {\n\n        a = 1;\n}',
            errors: [{ messageId: 'missing' }],
        },
        {
            // A nested class is reported and fixed independently of its parent.
            code: 'class C {\n\n    m() {\n\n        class D {\n            a = 1;\n        }\n    }\n}',
            output: 'class C {\n\n    m() {\n\n        class D {\n\n            a = 1;\n        }\n    }\n}',
            errors: [{ messageId: 'missing' }],
        },
    ],
});
