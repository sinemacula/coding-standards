/**
 * Tests for the align-doc-tags rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import rule from '../align-doc-tags.js';
import { ruleTester } from './tester.js';

ruleTester.run('align-doc-tags', rule, {
    valid: [
        // Both tags aligned at the default column.
        '/**\n * @author      Y\n * @copyright   2026 X\n */\nexport const a = 1;',
        '/**\n * Summary.\n *\n * @author      Y\n * @copyright   2026 X\n */\nexport const a = 1;',
        // Unlisted tags keep whatever spacing they carry.
        '/**\n * @param foo - a thing\n * @returns a value\n */\nexport const a = 1;',
        // A tag with no value has no spacing to align.
        '/**\n * @author\n */\nexport const a = 1;',
        // A line comment carrying a tag is not a documentation block.
        '// @author Y\nexport const a = 1;',
        // A plain block comment is not a documentation block.
        '/*\n * @author Y\n */\nexport const a = 1;',
        {
            code: '/**\n * @author   Y\n * @copyright 2026 X\n */\nexport const a = 1;',
            options: [{ column: 11 }],
        },
        // @copyright is outside the custom tag set.
        {
            code: '/**\n * @author      Y\n * @copyright 2026 X\n */\nexport const a = 1;',
            options: [{ tags: ['author'] }],
        },
        // A tag too long to reach the column is skipped.
        {
            code: '/**\n * @averyverylongtagname Y\n */\nexport const a = 1;',
            options: [{ tags: ['averyverylongtagname'] }],
        },
        // A wrapped value's continuation line is not a tag line.
        '/**\n * @copyright   2026 X\n * continued here\n */\nexport const a = 1;',
    ],
    invalid: [
        {
            code: '/**\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
            output: '/**\n * @author      Y\n * @copyright   2026 X\n */\nexport const a = 1;',
            errors: [
                { messageId: 'misaligned', data: { tag: 'author', column: 14 } },
                { messageId: 'misaligned', data: { tag: 'copyright', column: 14 } },
            ],
        },
        {
            // Over-padded values are pulled back to the column.
            code: '/**\n * @author            Y\n * @copyright        2026 X\n */\nexport const a = 1;',
            output: '/**\n * @author      Y\n * @copyright   2026 X\n */\nexport const a = 1;',
            errors: [{ messageId: 'misaligned' }, { messageId: 'misaligned' }],
        },
        {
            // A tab between tag and value normalises to spaces.
            code: '/**\n * @author\tY\n */\nexport const a = 1;',
            output: '/**\n * @author      Y\n */\nexport const a = 1;',
            errors: [{ messageId: 'misaligned' }],
        },
        {
            // Only the misaligned tag is reported.
            code: '/**\n * @author      Y\n * @copyright 2026 X\n */\nexport const a = 1;',
            output: '/**\n * @author      Y\n * @copyright   2026 X\n */\nexport const a = 1;',
            errors: [{ messageId: 'misaligned', data: { tag: 'copyright', column: 14 } }],
        },
        {
            // Matching is case-insensitive; the source casing is reported.
            code: '/**\n * @Author Y\n */\nexport const a = 1;',
            output: '/**\n * @Author      Y\n */\nexport const a = 1;',
            errors: [{ messageId: 'misaligned', data: { tag: 'Author', column: 14 } }],
        },
        {
            code: '/**\n * @author Y\n */\nexport const a = 1;',
            options: [{ column: 11 }],
            output: '/**\n * @author   Y\n */\nexport const a = 1;',
            errors: [{ messageId: 'misaligned', data: { tag: 'author', column: 11 } }],
        },
        {
            // A block after the imports is aligned too.
            code: "import { z } from 'z';\n/**\n * @author Y\n * @copyright   2026 X\n */\nexport const a = z;",
            output: "import { z } from 'z';\n/**\n * @author      Y\n * @copyright   2026 X\n */\nexport const a = z;",
            errors: [{ messageId: 'misaligned', data: { tag: 'author', column: 14 } }],
        },
        {
            // A wrapped value aligns on its opening line only.
            code: '/**\n * @copyright 2026 X\n * continued here\n */\nexport const a = 1;',
            output: '/**\n * @copyright   2026 X\n * continued here\n */\nexport const a = 1;',
            errors: [{ messageId: 'misaligned', data: { tag: 'copyright', column: 14 } }],
        },
    ],
});
