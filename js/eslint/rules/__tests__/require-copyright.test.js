/**
 * Tests for the require-copyright rule.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */

import rule from '../require-copyright.js';
import { ruleTester } from './tester.js';

ruleTester.run('require-copyright', rule, {
    valid: [
        // Tags in a top-of-file docblock.
        '/**\n * @copyright 2026 X\n * @author Y\n */\nexport const a = 1;',
        // Tags in the docblock that documents the export, after the imports.
        "import { z } from 'z';\n/**\n * A module.\n *\n * @copyright 2026 X\n * @author Y\n */\nexport const a = z;",
        // Description and tags together in one block.
        '/**\n * Summary line.\n *\n * @author Y\n * @copyright 2026 X\n */\nexport const a = 1;',
        // Extra tags alongside the required ones, in any order.
        '/**\n * @author Y\n * @license MIT\n * @copyright 2026 X\n */\nexport const a = 1;',
        // A shebang ahead of the documented block.
        '#!/usr/bin/env node\n/**\n * @copyright 2026 X\n * @author Y\n */\nconst a = 1;',
        // Tag matching is case-insensitive.
        '/**\n * @Copyright 2026 X\n * @AUTHOR Y\n */\nexport const a = 1;',
        // Custom required set: only @copyright.
        { code: '/**\n * @copyright 2026 X\n */\nexport const a = 1;', options: [{ tags: ['copyright'] }] },
    ],
    invalid: [
        {
            code: 'export const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            // Line comments carrying the tags do not count; a block comment is required.
            code: '// @copyright 2026 X\n// @author Y\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            // The block is missing @author.
            code: '/**\n * @copyright 2026 X\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            // The block is missing @copyright.
            code: '/**\n * @author Y\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            // The tags are split across two blocks; a single block must carry both.
            code: '/**\n * @copyright 2026 X\n */\n/**\n * @author Y\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            // A near-miss tag name does not satisfy the requirement.
            code: '/**\n * @copyrighted 2026 X\n * @author Y\n */\nexport const a = 1;',
            errors: [{ messageId: 'missing' }],
        },
        {
            // A custom required set naming a tag that is absent.
            code: '/**\n * @copyright 2026 X\n * @author Y\n */\nexport const a = 1;',
            options: [{ tags: ['copyright', 'author', 'license'] }],
            errors: [{ messageId: 'missing' }],
        },
    ],
});
