/**
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import rule from '../require-copyright.js';
import { ruleTester } from './tester.js';

ruleTester.run('require-copyright', rule, {
    valid: [
        // A leading block comment carrying both required tags.
        '/** @copyright 2026 X\n * @author A <a@b> */\nconst x = 1;',
        '/** @copyright 2026 X @author A */\nconst x = 1;',
        // A shebang may precede the header.
        '#!/usr/bin/env node\n/** @copyright 2026 X\n * @author A */\nconst x = 1;',
        // Leading blank lines are ignored.
        '\n\n/** @copyright 2026 X @author A */\nconst x = 1;',
        // A header-only file with no statements still has a header.
        '/** @copyright 2026 X\n * @author A */',
        // Tag matching is case-insensitive.
        '/** @Copyright 2026 X @AUTHOR A */\nconst x = 1;',
        // Order is irrelevant and unrelated tags may sit alongside.
        '/** @author A\n * @license MIT\n * @copyright 2026 X */\nconst x = 1;',
        // Prose before the tags is fine.
        '/** Header.\n * @copyright 2026 X\n * @author A\n */\nconst x = 1;',
        // Any block comment counts; the double asterisk is not required.
        '/* @copyright 2026 X @author A */\nconst x = 1;',
        // A pragma block ahead of the documentation block does not hide it.
        '/* @flow */\n/** @copyright 2026 X @author A */\nconst x = 1;',
        // A plain banner block ahead of the documentation block is tolerated.
        '/** intro banner */\n/** @copyright 2026 X @author A */\nconst x = 1;',
        // Required tags may be spread across the run of leading block comments.
        '/** @copyright 2026 X */\n/** @author A */\nconst x = 1;',
        // A configured tag set replaces the default.
        {
            code: '/** @license MIT */\nconst x = 1;',
            options: [{ tags: ['license'] }],
        },
        // An empty tag set requires only a leading block comment.
        {
            code: '/** anything */\nconst x = 1;',
            options: [{ tags: [] }],
        },
    ],
    invalid: [
        // No header at all.
        {
            code: 'const x = 1;',
            errors: [{ messageId: 'missingHeader', data: { tags: '@copyright, @author' } }],
        },
        // A line comment is not a header block.
        {
            code: '// @copyright 2026 X\n// @author A\nconst x = 1;',
            errors: [{ messageId: 'missingHeader', data: { tags: '@copyright, @author' } }],
        },
        // A block comment that follows code is not a header.
        {
            code: 'const x = 1;\n/** @copyright 2026 X @author A */',
            errors: [{ messageId: 'missingHeader', data: { tags: '@copyright, @author' } }],
        },
        // A line comment ahead of the block means the block is not first.
        {
            code: '// preamble\n/** @copyright 2026 X @author A */\nconst x = 1;',
            errors: [{ messageId: 'missingHeader', data: { tags: '@copyright, @author' } }],
        },
        // A shebang followed by code, with no header between them.
        {
            code: '#!/usr/bin/env node\nconst x = 1;',
            errors: [{ messageId: 'missingHeader', data: { tags: '@copyright, @author' } }],
        },
        // The header exists but omits @author.
        {
            code: '/** @copyright 2026 X */\nconst x = 1;',
            errors: [{ messageId: 'missingTag', data: { tag: 'author' } }],
        },
        // The header exists but omits @copyright.
        {
            code: '/** @author A */\nconst x = 1;',
            errors: [{ messageId: 'missingTag', data: { tag: 'copyright' } }],
        },
        // The header carries neither required tag.
        {
            code: '/** File does a thing. */\nconst x = 1;',
            errors: [
                { messageId: 'missingTag', data: { tag: 'copyright' } },
                { messageId: 'missingTag', data: { tag: 'author' } },
            ],
        },
        // A near-miss tag name does not satisfy the requirement.
        {
            code: '/** @copyrights 2026 X @author A */\nconst x = 1;',
            errors: [{ messageId: 'missingTag', data: { tag: 'copyright' } }],
        },
        {
            code: '/** @copyright 2026 X @authored A */\nconst x = 1;',
            errors: [{ messageId: 'missingTag', data: { tag: 'author' } }],
        },
        // A configured tag set is enforced in place of the default.
        {
            code: '/** @copyright 2026 X */\nconst x = 1;',
            options: [{ tags: ['license'] }],
            errors: [{ messageId: 'missingTag', data: { tag: 'license' } }],
        },
        // A pragma ahead of a header missing one tag reports only that tag.
        {
            code: '/* @flow */\n/** @copyright 2026 X */\nconst x = 1;',
            errors: [{ messageId: 'missingTag', data: { tag: 'author' } }],
        },
        // A leading block that lacks every tag still counts as the header.
        {
            code: '/* @flow */\nconst x = 1;',
            errors: [
                { messageId: 'missingTag', data: { tag: 'copyright' } },
                { messageId: 'missingTag', data: { tag: 'author' } },
            ],
        },
    ],
});
