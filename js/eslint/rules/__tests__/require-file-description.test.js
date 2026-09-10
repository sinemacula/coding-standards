/**
 * Tests for the require-file-description rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import rule from '../require-file-description.js';
import { ruleTester } from './tester.js';

ruleTester.run('require-file-description', rule, {
    valid: [
        // A summary above the tags: the shape the convention describes.
        '/**\n * Summary line.\n *\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
        // The summary opening on the same line as the block.
        '/** Summary line.\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
        // A summary running to several paragraphs before the tags.
        '/**\n * Summary line.\n *\n * A second paragraph.\n *\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
        // The header documenting the export, below the imports.
        "import { z } from 'z';\n/**\n * A module.\n *\n * @author Y\n * @copyright 2026 X\n */\nexport const a = z;",
        // A shebang ahead of the described header.
        '#!/usr/bin/env node\n/**\n * Summary line.\n *\n * @author Y\n * @copyright 2026 X\n */\nconst a = 1;',
        // A plain block comment carrying the tags is read the same way.
        '/*\n * Summary line.\n *\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
        // No block carries the tags, so there is no header to describe; the
        // absence is require-copyright's to report.
        'export const a = 1;',
        '/**\n * @author Y\n */\nexport const a = 1;',
        // An undescribed block that is not the header, alongside one that is.
        '/**\n * @author Y\n */\n/**\n * Summary line.\n *\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
        // A summary whose opening line is prose ending in an email address.
        '/**\n * Wraps mail sent to foo@example.com.\n *\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
        // A custom required set, describing the block it locates.
        {
            code: '/**\n * Summary line.\n *\n * @copyright 2026 X\n */\nexport const a = 1;',
            options: [{ tags: ['copyright'] }],
        },
    ],
    invalid: [
        {
            // Tags alone: the shape the rule exists to catch.
            code: '/**\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            // Blank lines ahead of the tags are not a description.
            code: '/**\n *\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            // An empty block carrying the tags on its opening line.
            code: '/** @author Y @copyright 2026 X */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            // Prose below the tags is a trailing note, not the summary the
            // convention puts above them.
            code: '/**\n * @author Y\n * @copyright 2026 X\n *\n * A trailing note.\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            // A wrapped tag value's continuation line does not pass as prose.
            code: '/**\n * @author Y\n * @copyright 2026 A Very Long Holder Name That\n *             wraps onto a second line\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            // A described block elsewhere does not describe the header.
            code: '/**\n * A note about the line below.\n */\n/**\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            // The header sitting below the imports, undescribed.
            code: "import { z } from 'z';\n/**\n * @author Y\n * @copyright 2026 X\n */\nexport const a = z;",
            errors: [{ messageId: 'missing' }],
        },
        {
            // A custom required set locating an undescribed block.
            code: '/**\n * @copyright 2026 X\n */\nexport const a = 1;',
            options: [{ tags: ['copyright'] }],
            errors: [{ messageId: 'missing' }],
        },
    ],
});
